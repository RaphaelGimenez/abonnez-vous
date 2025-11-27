# Mini projet Symfony

Ce dépôt contient un mini projet développé avec le framework Symfony. Il me permet de mettre en pratique les concepts fondamentaux de Symfony : gestion des routes, contrôleurs, entités, formulaires et services.

Le projet implémente un système d'abonnement selon différents plans tarifaires. Les utilisateurs peuvent s'inscrire, choisir un plan d'abonnement et gérer leur abonnement via une interface simple.

## Développement


### Configuration devcontainer

Le projet possède une configuration [devcontainer](.devcontainer/devcontainer.json) pour faciliter le développement.

L'environnement devcontainer nécessite Docker/Podman et l'extension Remote - Containers sur VS Code afin d'être utilisé.

L'environnement se base sur ce [docker-compose.yml](.devcontainer/docker-compose.yml) et contient :
- Le service `dev` qui exécute le conteneur de développement basé sur l'image [mcr.microsoft.com/devcontainers/php](https://mcr.microsoft.com/en-us/artifact/mar/devcontainers/php/about)
- Le service `stripe-mock` qui exécute une instance locale de [stripe-mock](github.com/stripe/stripe-mock) utiliser lors des tests d'intégration.
- Le service `stripe-cli` permettant de rediriger les webhooks Stripe vers le conteneur `dev`.

### DB

Le projet utilise Sqlite comme base de données pour simplifier la configuration.

### Configuration Stripe

Le projet utilise Stripe pour la gestion des paiements et des abonnements. Il est nécessaire de configurer les clés d'API Stripe dans les fichiers suivants :
- [.env.local](.env.local) -> `STRIPE_SECRET_KEY`, `STRIPE_PUBLIC_KEY` et `STRIPE_WEBHOOK_SECRET`
- [.devcontainer/.env](./.devcontainer/.env) -> `STRIPE_API_KEY`
- [.env.test](.env.test) -> `STRIPE_WEBHOOK_SECRET`

En local `STRIPE_WEBHOOK_SECRET` doit être récupéré dans les logs du service `stripe-cli` après avoir démarré le conteneur devcontainer.

Dans l'environnement de test, le client stripe est configuré pour se connecter à l'instance locale de `stripe-mock` voir [config/services.yaml](config/services.yaml#L30)

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
- Protection CSRF pour les formulaires d'abonnement.
- Paiement sécurisé via Stripe.
- Webhooks Stripe pour gérer les événements d'abonnement :
	- Hook 'checkout.session.completed' pour activer l'abonnement après un paiement réussi.
- Gestion des abonnements via une interface utilisateur simple.

## Technologies utilisées
- Symfony 7
- Doctrine ORM
- SQLite pour la base de données
- Twig pour le templating
- Stripe pour la gestion des paiements et des abonnements
- Tailwind CSS pour le design

## Tests

Des test unitaires et d'intégration sont inclus pour valider les fonctionnalités principales du projet. Ils peuvent être exécutés avec PHPUnit :

```bash
composer test
composer test:coverage
composer test:coverage-text
```
