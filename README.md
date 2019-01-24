Plugin Pluviometrie Z-Rain de Popp

Lors de l'inclusion du materiel Z-Rain de Popp, la box Eedomus met en place 2 peripheriques l'un permettant de visualiser "le cumul de pluviometrie" en m3, l'autre permettant de voir la "pluviometrie horaire" en mm/h.
Ce plugin permet de mettre en place un actionneur HTTP qui calcule la pluviometrie journaliere et mensuelle en mm a partir du cumul de pluviometrie et affiche les resultats dans 2 etats.
L'actionneur HTTP s'appuie sur un script PHP qui utilise l'API Eedomus.

Pre-requis 
Le pluviometre Z-Rain doit avoir ete installe avec presence des 2 peripheriques "pluviometrie horaire" et "cumul de pluviometrie".

Installation du plugin
Elle se fait a partir du store Eedomus en selectionnant le plugin "Pluviometrie ZRain".
Lors de l'installation, il faut fournir l'ID du cumul de pluviometrie qui a ete installe lors de l'inclusion du module Z-Rain.
Le plugin va installer 3 peripheriques : 

1/ un actionneur HTTP : "controle de la pluviometrie" qui assure la mise a jour des pluviometries journalieres et mensuelles par le biais d'un script PHP et qui envoit les valeurs aux peripheriques respectifs.
2/ un peripherique "cumul journalier" qui affiche la pluviometrie du jour
3/ un peripherique "cumul mensuel" qui affiche la pluviometrie du mois.

Reglages complementaires
1/ Il faut modifier les unites du controle "cumul de pluviometrie" mis en place lors de l'installation du pluviometre Z-Rain. Il faut passer les unites de m3 en mm. 
Pour cela il faut aller dans le parametrage Z-Wave du pluviometre et modifier le parametre no 4 (Meter Multiplier) en lui assignant la valeur 1000.

2/ Il est souhaitable mais pas indispensable de rajouter une regle de mise a jour journaliere des cumuls du type :
Tous les jours a 0h et 1mn
peripherique de pluviometrie MAJ.
(cette regle n'a pu etre integree dans le plugin car le codage des declenchements horaires dans les regles n'est pas documente)

Fonctionnement
Chaque bascule du pluviometre (tous les 0,5 mm de pluie) entraine une mise a jour automatique du cumul de pluie annuel.
En effet, la modification du cumul annuel entraine le declenchement de la regle qui commande la mise a jour des cumuls journaliers et mensuels.
Il n'est donc pas necessaire d'activer le polling de l'actionneur HTTP ce qui épargne du temps CPU.

Extensions prevues:
1/ Il est prevu de faire un releve de pluviometrie sur 3 jours pour pouvoir piloter un arrosage automatique fonction de la pluviometrie.

2/ Il serait aussi interessant d'affiner la notion de pluie forte (plus de 5 mm en 5 mn ou 17l/h). 

