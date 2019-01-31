<?  
    //**********************************************************************************************************
    // V1.21 : Script de pluviometrie pour ZRain
    // Mise à jour : 31/01/2019
    // Ajout des fonctions testarrosage et pluieintense
    // auteur: germam
    //*******************************************************************************************************
    // recuperation des infos depuis la requete
    // API DU PERIPHERIQUE CUMUL PLUIE ANNEE ZRAIN - [VAR1]
    $IDCumulZrain = getArg("id_cumul_zrain", $mandatory = true, $default = 'undefined');
    // API DES PERIPHERIQUE CUMULS PLUIE MOIS ET JOUR - [VAR2]
    $IDListCumuls = getArg("id_cumuls", $mandatory = false, $default = 'undefined');
    if ($IDListCumuls != 'undefined')
    {
        $Cumuls = explode(",",$IDListCumuls);
        $IDPluieMois = $Cumuls[0];
        $IDPluieJour = $Cumuls[1];
    }
    //API DES PERIPHERIQUES DE TEST D'ARROSAGE ET DE PLUIE INTENSE [VAR3]
    $IDListTests = getArg("id_arrosage", $mandatory = false, $default = 'undefined');
    if ($IDListTests != 'undefined')
    {
        $Tests = explode(",",$IDListTests);
        $IDTestArrosage = $Tests[0];
        $IDPluieIntense = $Tests[1];
        $IDMacroPluieIntense = $Tests[2];
    }
    //ACTION DEMANDEE
    $action = getArg("action", $mandatory = true, $default = '');
    // Nb de jours d'observation de pluviometrie pour declencher l'arrosage
    $NbJourObservation = getArg("nbjoursobservation", $mandatory = false, $default = 3);
    // Cumul minimum de pluie  en mm pour ne pas arroser
    $CumulMini = getArg("cumulminisansarrosage", $mandatory = false, $default = 5);
    // Seuil de declenchement de l'alerte pluie intense (normalement 5mm / 5mn ou 17mm / 1h)
    $SeuilPluieIntense = getArg("seuilpluieintense", $mandatory = false, $default = 5);
    $SeuilPluieIntenseMois = getArg("seuilpluieintensemois", $mandatory = false, $default = 17);
   
    sdk_main($action, $IDCumulZrain, $IDPluieMois, $IDPluieJour, $IDTestArrosage, $IDPluieIntense, $NbJourObservation, $CumulMini, $SeuilPluieIntense, $IDMacroPluieIntense, $SeuilPluieIntenseMois);

    function sdk_main($action, $IDCumulZrain, $IDPluieMois, $IDPluieJour, $IDTestArrosage, $IDPluieIntense, $NbJourObservation, $CumulMini, $SeuilPluieIntense, $IDMacroPluieIntense, $SeuilPluieIntenseMois)
    {
        $zrain = new sdk_rain ();
        $zrain->sdk_init($IDCumulZrain);
        $peripharrosage = ($IDTestArrosage != "");
        if ($IDCumulZrain != 'undefined')
        {
            switch ($action) 
            {
                case "updateconso" :
                    $zrain->sdk_updateconso($IDPluieMois, $IDPluieJour );
                    if ($peripharrosage)
                    {
                        $zrain->sdk_pluieintense($IDPluieIntense, $SeuilPluieIntense, $IDMacroPluieIntense, $SeuilPluieIntenseMois);
                        $zrain->sdk_check_arrosage($IDTestArrosage, $NbJourObservation, $CumulMini);
                    }
                    break;
                case "raz" : 
                    $zrain->sdk_raz($IDPluieMois, $IDPluieJour);
                    break;
                case "arrosage" :
                    if ($peripharrosage)
                         $zrain->sdk_check_arrosage($IDTestArrosage, $NbJourObservation, $CumulMini);
                    break;
                case "pluieintense" :
                    if ($peripharrosage)
                        $zrain->sdk_pluieintense($IDPluieIntense, $SeuilPluieIntense);
                    break;
            }
        }
    }
  
    class sdk_rain 
    {
        protected $id_cumul_zrain;
        protected $formatdate;
        protected $firstinstall;
         
        public function sdk_init($IDCumulZrain) 
        {
            $this->id_cumul_zrain = $IDCumulZrain;
            $this->formatdate = "Y-m-d H:i:s";
            $this->firstinstall = false;
        }

        protected function sdk_init_releves_pluie() 
        {
            $value = getValue($this->id_cumul_zrain);
            $etat_compteur = $value['value']; 
            
            $tab_init = array ("jour_global" => 0.0000, "mois_global" => 0.0000, "last_cumul"=> 0.0000,"last_date_mesure" => date('d')."-00:00");
            saveVariable('RelevesPluie'.$this->id_cumul_zrain, $tab_init);
            $this->firstinstall = true;
            return $tab_init;
        }

        protected function sdk_get_last_releve_pluie() 
        {
            $preload = loadVariable('RelevesPluie'. $this->id_cumul_zrain);
            if ($preload != '' && substr($preload, 0, 8) != "## ERROR") 
            {	
                $tab_releve = $preload;
            } else 
            {
                $tab_releve = $this->sdk_init_releves_pluie();
            }
            return $tab_releve;
        }

        protected function sdk_save_new_releve_pluie($tab_releves) 
        {
            $new_tab_releves = $tab_releves;
            $new_tab_releves['last_date_mesure'] = date('d')."-".date("H").":".date("i");
            saveVariable('RelevesPluie'.$this->id_cumul_zrain, $new_tab_releves);
        }
        
        protected function sdk_update_control_value ($IDPluieMois, $IDPluieJour, $releve_jour_global , $releve_mois_global)
        {
            if ($IDPluieJour != "")
                setValue($IDPluieJour,round($releve_jour_global,3),false,true);
            if ($IDPluieMois != "")
                setValue($IDPluieMois,round($releve_mois_global,3),false,true);
        }
    
        protected function sdk_get_cumul_periode ($delaijour, $delaiminute)
        {
            $StartDate = date($this->formatdate,mktime(date("H"),date("i") - $delaiminute, date("s"), date("m")  , date("d") - $delaijour , date("Y")));
            $EndDate = date($this->formatdate);
            
            // recupere les enregistrements de cumul de pluie sur la periode 
            $url = "https://api.eedomus.com/get?action=periph.history";
            $url .= "&periph_id=" . $this->id_cumul_zrain;
            $url .= "&start_date=" .$StartDate;
            $url .= "&end_date=" .$EndDate;
            $arr = sdk_json_decode(utf8_encode(httpQuery($url,'GET')));
            $records = $arr["body"]["history"];
            
            // Calcul du cumul de pluie sur la pÃ©riode
            $Cumul = 0;
            $nbrecords=count($records);
            if ($nbrecords > 1) 
            {
                $Cumul = (int)$records[0][0] - (int)$records[$nbrecords - 1][0];
            }
            else if ($nbrecords == 1)
            {
                $dernier_releve = $this->sdk_get_last_releve_pluie();
                $last_etat_compteur = $dernier_releve["last_cumul"];
                $Cumul = (int)$records[0][0] - $last_etat_compteur;
            }
            return $Cumul;
        }
        
        public function sdk_updateconso($IDPluieMois, $IDPluieJour) 
        {
            // recuperation de la valeur actuelle du compteur
            $value = getValue($this->id_cumul_zrain);
            $etat_compteur = $value['value']; 
            
            // On recupere les dernieres mesures      
            $dernier_releve = $this->sdk_get_last_releve_pluie();
            if ($this->firstinstall == false)
            {
                $last_etat_compteur = $dernier_releve["last_cumul"];
                $releve_conso = 0;
                
                // recuperation du cumul depuis le dernier releve
                if ($etat_compteur < $last_etat_compteur) 
                {
                    $releve_conso = round($etat_compteur, 4);
                }
                else 
                {
                    $releve_conso = round(($etat_compteur - $last_etat_compteur), 4);
                }
                
                // recuperation des derniers releves sauvegardes
                $lasttime = substr($dernier_releve['last_date_mesure'], 3, 5);
                $lastday = substr($dernier_releve['last_date_mesure'], 0, 2);
                $releve_jour_global = $dernier_releve['jour_global'];
                $releve_mois_global = $dernier_releve['mois_global'];

                // si la derniere mesure est la veille ou le mois precedent RAZ mesure
                if ($lastday != date('d')) 
                {
                    $releve_jour_global = 0;
                    if (date('j') == 1) 
                    {
                        $releve_mois_global = 0;
                    }
                }
                    
                // Mise a jour et sauvegarde des derniers releves
                $releve_jour_global += $releve_conso;
                $dernier_releve['jour_global'] = $releve_jour_global;
                $releve_mois_global += $releve_conso;
                $dernier_releve['mois_global'] = $releve_mois_global;
                $dernier_releve["last_cumul"] = $etat_compteur;
                $this->sdk_save_new_releve_pluie($dernier_releve);
                // Mise a jour des derniers releves sur les controles
                $this->sdk_update_control_value ($IDPluieMois, $IDPluieJour, $releve_jour_global , $releve_mois_global);
            }
            else
                $this->sdk_update_control_value ($IDPluieMois, $IDPluieJour, 0 , 0);
        }
            
        public function sdk_raz($IDPluieMois, $IDPluieJour)
        {
            $tab_init = $this->sdk_init_releves_pluie();
            $this->sdk_update_control_value ( $IDPluieMois, $IDPluieJour, 0 , 0);
        }

        public function sdk_check_arrosage($IDTestArrosage, $NbJourObservation, $CumulMini)
        {
            $cumul = $this->sdk_get_cumul_periode ($NbJourObservation, 0); 
            $consigne = 1; /* inutile */
            if ($cumul < $CumulMini)
            { 
                $consigne = 0; /*conseille*/
                // On regarde s'il n'est pas en train de pleuvoir
                $cumul = $this->sdk_get_cumul_periode (0, 5);
                if ($cumul > 0)
                    $consigne = 1;
            }
            setValue($IDTestArrosage, $consigne,false,true);
        }

        public function sdk_pluieintense($IDPluieIntense, $SeuilPluieIntense, $IDMacroPluieIntense, $SeuilPluieIntenseMois) 
        {
            if ($IDPluieIntense != "")
            {
                $consigne = 0; /* Pas de pluie intense */
                // test si cumul sur 5 mn > $SeuilPluieIntense (5mm)
                $cumul = $this->sdk_get_cumul_periode (0, 5); 
                if ($cumul > $SeuilPluieIntense) 
                    $consigne = 1; /* Pluie intense */
                else
                {           
                    // test si cumul horaire > 17mm
                    $cumul = $this->sdk_get_cumul_periode (1, 0); 
                    if ($cumul > $SeuilPluieIntenseMois) 
                        $consigne = 1;
                }
                if ($consigne == 1)
                    setMacro($IDPluieIntense, $IDMacroPluieIntense);                
                else
                    setValue($IDPluieIntense,$consigne,false,true);
            }
        }
    }
    sdk_header('text/xml');
?>
