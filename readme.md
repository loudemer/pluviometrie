# Plugin Pluviometrie Z-Rain de Popp poue box Eedomus+
**Version 1.21**
**Script cree par Germam**

Lors de l'inclusion du materiel Z-Rain de Popp, la box Eedomus met en place 2 peripheriques l'un permettant de visualiser "le cumul de pluviometrie" en m3, l'autre permettant de voir la "pluviometrie horaire" en mm/h.

Ce plugin permet de compléter cette installation en mettant en place un actionneur HTTP qui calcule la pluviometrie journaliere et mensuelle en mm a partir du cumul de pluviometrie et affiche les resultats dans 2 peripheriques.

L'actionneur HTTP s'appuie sur un script PHP qui utilise l'API Eedomus.

## Pre-requis 

Le pluviometre Z-Rain doit avoir ete installe avec presence des 2 peripheriques "pluviometrie horaire" et "cumul de pluviometrie".

## Installation du plugin

Elle se fait a partir du store Eedomus en selectionnant le plugin "Pluviometrie ZRain".

### Champs a renseigner lors de l'installation du plugin

- **designer la piece d'installation**

- **fournir l'ID du cumul de pluviometrie** qui a ete installe lors de l'inclusion du module Z-Rain.

Le plugin peut installer 5 peripheriques : 

        1/ un actionneur HTTP : "controle de la pluviometrie" qui assure la mise a jour des pluviometries journalieres et mensuelles par le biais d'un script PHP et qui envoit les valeurs aux peripheriques respectifs.

        2/ un peripherique "cumul journalier" qui affiche la pluviometrie du jour

        3/ un peripherique "cumul mensuel" qui affiche la pluviometrie du mois.

        4/ un peripherique "Arrosage" qui affiche si un arrosage est necessaire en fonction du cumul de pluie des 3 derniers jours

        5/ un peripherique "Pluie Intense" qui affiche si la pluie depasse 5 mm / 5mn ou 17 mm/h

- installer ou non **les peripheriques "arrosage et Pluie Intense"** en cochant ou non la case.

## Reglages complementaires

1/ Il faut **modifier les unites du controle "cumul de pluviometrie"** mis en place lors de l'installation du pluviometre Z-Rain. Il faut passer les unites de m3 en mm. 
Pour cela il faut aller dans le parametrage Z-Wave du pluviometre Z-Rain et modifier le parametre no 4 (Meter Multiplier) en lui assignant la valeur 1000.


## Fonctionnement

Chaque bascule du pluviometre (tous les 0,5 mm de chute de pluie) entraine une mise a jour automatique du cumul de pluie annuel.

La modification du cumul annuel entraine ensuite le declenchement d'une regle qui commande la mise a jour des cumuls journaliers et mensuels ainsi qu'une evaluation de l'intensite de la pluie et une evaluation du cumul de pluie pour conseiller ou non l'arrosage avec la mise à jour des controles respectifs.

En cas de pluie intense (si le cumul de pluie depasse 5 mm/5mn ou 17 mm/h), l'peripherique "pluie intense" bascule à pluie intense ce qui vous permet d'ajouter une alerte avec  une regle basee sur la modification du peripherique pour la gerer.
An noter que l'alerte se neutralise au bout de 5 mn à moins que l'episode de pluie intense continue.

Pour l'arrosage, le peripherique "Arrosage" bascule a "conseille" lorsque le cumul de pluie des 3 derniers jours est inferieur à 5 mm et lorsqu'il ne pleut pas. A noter que l'utilisation du pluviometre est beaucoup plus precise que les releves internet surtout lors des orages qui peuvent etre tres localises.

Il n'est pas necessaire d'activer le polling de l'actionneur HTTP ce qui epargne du temps CPU.

Lorsque l'alerte pluie intense est déclenchee, la pluviometrie est reevalue automatiquement toutes les 5 mn pour desarmer, si besoin, l'alerte.

La consigne d'arrosage (conseille ou non) est reevaluee chaque jour a minuit. On peut lancer une mise a jour dans une regle en lancant l'action "test arrosage" du peripherique "controle pluviometrie"

## Nouveautes de la version 1.1

Ajout des fonctionnalites **Pluie intense** et **Conseil arrosage**
Corrections de bugs d'installation


