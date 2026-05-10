# Darquest, détails d’implémentation du sprint 1 , acheter des items

Date début : semaine du 09 mars. Date de revue et remise : le 30 mars

Liste des fonctionnalités : Voici la liste des fonctionnalités attendues pour le sprint 1. (ce sont les

fonctionnalités et non des user-stories. Pour les US, referez-vous à votre Backlog de sprint :

Vous devez modifier votre backlog de sprint 1 en fonction des fonctionnalités ci-dessous :

```
Énoncé des fonctionnalités Priorité
Afficher tous les items avec les tris demandés M
Rechercher des items selon un critère avec les tris demandés M
Voir le détail d’un item M
Inscription d’un joueur et connexion de celui-ci M
Ajouter un item au panier M
Supprimer un item du panier M
Modifier la quantité d’un item dans panier M
Consulter son panier M
Payer son panier
```
- Mise à jour du solde du joueur
- Mise à jour de l’inventaire du joueur
- Mise à jour de la quantité en inventaire. (Items)
- Vider le panier

```
M
```
```
Consulter l’inventaire du joueur M
Rechercher des items selon plusieurs critères (critères combinés) S
```
À considérer :

- Le prix d’un item est de l’ordre de 1 à 100 pièces d’or.
- Le montant initial d’un joueur est :
    o 1000 pièces d’or
    o 1000 pièces d’argent
    o 1000 pièces de bronze
- Une pièce d’or vaut : 10 pièces d’argent. Une pièce d’argent vaut 10 pièces de bronze.

```
Stories techniques Détails
Vérifier la connexion au
serveur PHP )(***)
```
```
Chaque membre de l’équipe doit faire le test qu’il peut bien transférer
une page sur le serveur
Créer et peupler la base de
données
```
```
Au moins : 5 joueurs.
Au moins 5 items de chaque type. ( voir exemple de procédure stockée
pour simplifier l’insertion )
Configurer GitHub (***) Chaque membre de l’équipe doit avoir un compte GitHub
Un membre de l’équipe sera chargé de créer le dépôt dans son propre
compte GitHub et doit inviter les membres de son équipe.
Créer son tableau de tâches
verticales pour le sprint 1.
→Post-it au mur. (***)
Outils en dehors de la
classe : Jira ou Trello.
```
```
Même principe. Un membre vac créer le Trello pour le sprint 1 et invite
les co-équipiers.
```
**Note** : (***) doit déjà être faites. On teste en classe.


## Les tests : À titre d’indication, voici un ensemble non exhaustif de tests unitaires qui seront

réalisés pour Darquest-sprint1.

```
Test unitaires, le compte du joueur
```
- Validation de tous les champs.
- L’alias est unique.
- Le courriel est unique s’il y a lieu
- Afficher les cas d’erreur (connexion multiple, alias dupliqué etc..)
- Connexion réussie : afficher les infos pertinentes du joueur.
- Connexion non réussie : afficher le message erreur

```
Test unitaires, recherches d’item
```
- Aucun critère
- Tous les critères
- Recherche selon un critère : Exemple Potion. Tous les critères seront testés
- Recherche selon deux critères. Exemple Potion et Arme. Toutes les combinaisons seront
    testées
- Recherche selon trois critères. Toutes les combinaisons seront testées
- Afficher les détails pertinents pour un item
- Trier le résultat de la recherche.
- Interface conviviale et intuitive. Facilité de navigation

```
Test unitaires, le panier
```
- Ajouter un item au panier : Quantité disponible, le montant s’affiche. Le montant total du
    panier s’affiche
- Modifier la quantité d’un item : Quantité disponible, le montant est mis à jour. Le
    montant total du panier est mis à jour
- Supprimer un item du panier : le montant total du panier se met à jour
- Ajouter un item au panier, quantité non disponible
- Modifier la quantité du panier, quantité non disponible
- Payer son panier : le joueur a le montant.
    o Le nombre de pièces d’or, d’argent, et de bronze est mis à jour
    o L’inventaire du joueur est mis à jour
    o Le magasin (la boutique) ou l’item est mis à jour.
    o Le panier est vidé.
- Payer son panier : le joueur n’a pas le montant : indiquer que le jouer n’a pas le solde
    pour payer.
- Acheter sans se connecter. --> erreur
- Acheter un sort si le joueur n’est pas mage. --> erreur

```
Test unitaires, l’inventaire du joueur
```
- Aucun critère : on affiche tous les items.
- (optionnel) afficher selon un critère : Exemple Potion. Tous les critères seront testés
- (optionnel) afficher selon des critères combinés


# Grille de correction du sprint 1

**Éléments évalués Notes Remarques**
Backlog du sprint 1 : ici soit vous avez 0 ou vous avez 8. Il faudra
remettre un backlog du sprint 1, en tenant compte de l’ensemble des
remarques faites au sprint 0→ Date de remise le 09 mars.

### / 8

Préparation à la revue(Démonstration, réponse aux questions) / 7
Fonctionnement et robustesse : les éléments livrés fonctionnent bien. Ne
plantent pas. L’application est bien testée.
Fonctionnalités livrées : respect des fonctionnalités livrées pour le sprint
en cours

```
/ 40 Également
voir note 2
plus bas.
```
Éléments de conception (interface, BD) / 15 Également
voir note 2
plus bas.
Total note commune sur 70 70
Participation au développement du sprint : Tâches réalisées, qualité du
travail livré.

/ 15 Également
voir note 1
plus bas.
Participation aux rencontres d’équipe. / 10
Évaluation par les pairs / 5
Total / 100

```
Extrait du plan de cours :
```
```
Note 1 : Certaines parties de cette évaluation sont individuelles. La participation aux scrums quotidiens est
obligatoire pour justifier votre participation au travail en cours. Un nombre minimum de participation sera
exigé et vous sera communiqué au début de chaque sprint.
```
```
Note 2 : L’objectif de la PFI étant : « Utiliser une méthode de gestion agile de projet afin de concevoir, en
équipe, une application » Le projet et la gestion de projet sont évalués comme un tout.
```

