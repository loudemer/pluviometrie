<?php  
    //**********************************************************************************************************
    // V1.0 : Script de pluviometrie pour ZRain
    // auteur: germam
    //*******************************************************************************************************
    // recuperation des infos depuis la requete
    // API CONSO CUMULEE ANNEE ZRAIN - VAR1
    $IDCumulZrain = getArg("id_cumul_zrain", $mandatory = true, $default = 'undefined');
    // API CONSO CUMULEE MOIS - VAR2
    $IDPluieMois = getArg("id_cumul_mois", $mandatory = true, $default = 'undefined');
    // API DU PERIPHERIQUE APPELANT LE SCRIPT : cumul pluie jour
    $IDpluieJour = getArg('id_cumul_jour', $mandatory = true, $default = 'undefined');
    // ACTION
    $action = getArg("action", $mandatory = true, $default = '');
   
    sdk_main($action,$IDCumulZrain,$IDPluieMois,$IDpluieJour);

    function sdk_main($action,$IDCumulZrain,$IDPluieMois,$IDpluieJour)
    {
        $zrain = new sdk_rain ();
        $zrain->sdk_init($IDCumulZrain,$IDPluieMois,$IDpluieJour);

        if($action == "updateconso"){
            $zrain->sdk_updateconso();
        } else if ($action == "raz") {
            $zrain->sdk_raz();
        }
    }
  
    class sdk_rain 
    {
        protected $id_cumul_zrain;
        protected $id_pluie_mois;
        protected $id_pluie_jour;
        protected $maintenant;
         
        public function sdk_init($IDCumulZrain , $IDPluieMois , $IDpluieJour) {
            $this->id_cumul_zrain = $IDCumulZrain;
            $this->id_pluie_mois = $IDPluieMois;
            $this->id_pluie_jour = $IDpluieJour;
            $this->maintenant = date("H").":".date("i");
        }

        protected function sdk_init_releves_pluie() {
            $tab_init = array ("jour_global" => 0.0000, "mois_global" => 0.0000, "last_cumul"=> 0.0000,
            "last_date_mesure" => date('d')."-00:00");
            saveVariable('RelevesPluie'.$this->id_cumul_zrain, $tab_init);
            return $tab_init;
        }

        protected function sdk_get_last_releve_pluie() {
            $preload = loadVariable('RelevesPluie'. $this->id_cumul_zrain);
            if ($preload != '' && substr($preload, 0, 8) != "## ERROR") {	
                $tab_releve = $preload;
            } else {
                $tab_releve = $this->sdk_init_releves_pluie();
            }
            return $tab_releve;
        }

        protected function sdk_save_new_releve_pluie($tab_releves) {
            $new_tab_releves = $tab_releves;
            $new_tab_releves['last_date_mesure'] = date('d')."-".$this->maintenant;
            saveVariable('RelevesPluie'.$this->id_cumul_zrain, $new_tab_releves);
        }
        
        protected function sdk_update_control_value ( $releve_jour_global , $releve_mois_global){
            setValue($this->id_pluie_jour,round($releve_jour_global,3));
            setValue($this->id_pluie_mois,round($releve_mois_global,3));
        }
    
        protected function sdk_save_last_releve_pluie($tab_cpt) {
            saveVariable('CompteurPluie'.$this->id_cumul_zrain, $tab_cpt);
        }
        
        public function sdk_updateconso() {
            // recuperation de la valeur actuelle du compteur
            $value = getValue($this->id_cumul_zrain);
            $etat_compteur = $value['value']; 
            
            // On recupere les dernieres mesures      
            $dernier_releve = $this->sdk_get_last_releve_pluie();
            $last_etat_compteur = $dernier_releve["last_cumul"];
            $releve_conso = 0;
            
            // recuperation du cumul depuis le dernier releve
             if ($etat_compteur < $last_etat_compteur) {
                $releve_conso = round($etat_compteur, 4);
            }
            else {
                $releve_conso = round(($etat_compteur - $last_etat_compteur), 4);
            }
            
            // recuperation des derniers releves sauvegardes
            $lasttime = substr($dernier_releve['last_date_mesure'], 3, 5);
            $lastday = substr($dernier_releve['last_date_mesure'], 0, 2);
            $releve_jour_global = $dernier_releve['jour_global'];
            $releve_mois_global = $dernier_releve['mois_global'];

            // si la derniere mesure est la veille ou le mois precedent RAZ mesure
            if ($lastday != date('d')) {
                $releve_jour_global = 0;
                if (date('j') == 1) {
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
            
            // Mise à jour des derniers releves sur les controles
            $this->sdk_update_control_value ( $releve_jour_global , $releve_mois_global);
        }
            
        public function sdk_raz(){
            $tab_init = $this->sdk_init_releves_pluie();
            $this->sdk_update_control_value ( round($tab_init['jour_global'],3) , round($tab_init['mois_global'],3));
            
            $this->sdk_save_last_releve_pluie($tab_cpt);
        }
    }
        
    sdk_header('text/xml');
?>
