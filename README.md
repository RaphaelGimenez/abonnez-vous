# Mini projet Symfony

Ce dépôt contient un mini projet développé avec le framework Symfony. Il me permet de mettre en pratique les concepts fondamentaux de Symfony : gestion des routes, contrôleurs, entités, formulaires et services.

Le projet implémente un système d'abonnement selon différents plans tarifaires. Les utilisateurs peuvent s'inscrire, choisir un plan d'abonnement et gérer leur abonnement via une interface simple.

## Développement


### Configuration devcontainer

Le projet possède une configuration [devcontainer](.devcontainer/devcontainer.json) pour faciliter le développement.

L'environnement devcontainer nécessite Docker/Podman et l'extension Remote - Containers sur VS Code afin d'être utilisé.

### DB

Le projet utilise Sqlite comme base de données pour simplifier la configuration.

### Installation et exécution

1. Cloner le dépôt :

	 ```bash
	 git clone https://github.com/RaphaelGimenez/abonnez-vous.git
	 cd abonnez-vous
	 ```

2. Ouvrir le projet dans un conteneur devcontainer via VS Code.
3. Installer les dépendances avec Composer :

	 ```bash
	 composer install
	 ```
4. Configurer la base de données et exécuter les migrations :

	 ```bash
	 php bin/console doctrine:migrations:migrate
	 ```
5. Lancer le serveur de développement Symfony :
	 ```bash
	 composer serve
	 ```
6. Lancer le builder de styles Tailwind CSS :
	 ```bash
	 php bin/console tailwind:build --watch
	 ```
7. Accéder à l'application via `http://localhost:8000` dans votre navigateur.

## Fonctionnalités

- Inscription et authentification des utilisateurs.
- Choix entre plusieurs plans d'abonnement.
- Gestion des abonnements via une interface utilisateur simple.
- Protection CSRF pour les formulaires d'abonnement.

## Technologies utilisées
- Symfony 7
- Doctrine ORM
- SQLite pour la base de données
- Twig pour le templating
- Tailwind CSS pour le design

## Tests

Des test unitaires et d'intégration sont inclus pour valider les fonctionnalités principales du projet. Ils peuvent être exécutés avec PHPUnit :

```bash
php bin/phpunit
```
