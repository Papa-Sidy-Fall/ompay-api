# 🚀 Déploiement OmPay API sur Render

## Prérequis

- Compte Render (https://render.com)
- Variables d'environnement configurées

## Configuration Render

### 1. Variables d'environnement à définir dans Render

Dans votre dashboard Render, allez dans **Environment** et ajoutez ces variables :

#### Application
```
APP_NAME=OmPay API
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_APP_KEY_HERE
LOG_CHANNEL=stack
LOG_LEVEL=error
```

#### Base de données (PostgreSQL)
Render créera automatiquement la DB PostgreSQL. Les variables seront automatiquement définies :
```
DB_CONNECTION=pgsql
DB_HOST=auto
DB_PORT=auto
DB_DATABASE=auto
DB_USERNAME=auto
DB_PASSWORD=auto
```

#### Cache & Sessions
```
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

#### Mail (Gmail recommandé)
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-email@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-app
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ompay.com
MAIL_FROM_NAME=OmPay
```

#### Twilio (SMS)
```
TWILIO_SID=votre-twilio-sid
TWILIO_TOKEN=votre-twilio-token
TWILIO_FROM=+1234567890
```

#### Brevo (Email alternatif)
```
BREVO_API_KEY=votre-brevo-api-key
```

### 2. Déploiement

1. **Connectez votre repository GitHub** à Render
2. **Sélectionnez le service Web**
3. **Choisissez Docker** comme runtime
4. **Spécifiez le Dockerfile** : `./Dockerfile`
5. **Définissez les variables d'environnement** (voir ci-dessus)
6. **Ajoutez une base de données PostgreSQL**
7. **Déployez**

### 3. Health Check

L'API sera disponible sur : `https://votre-app.render.com`

Health check endpoint : `https://votre-app.render.com/api/documentation`

## Commandes importantes

### Génération de clé d'application
```bash
php artisan key:generate
```
Copiez la valeur `APP_KEY` générée dans les variables Render.

### Installation Passport (fait automatiquement)
```bash
php artisan passport:install
php artisan passport:client --personal
php artisan passport:client --password
```

### Migrations (faites automatiquement)
```bash
php artisan migrate
```

## Dépannage

### Erreur de connexion DB
- Vérifiez que la DB PostgreSQL est bien créée dans Render
- Vérifiez les variables `DB_*`

### Erreur Passport
- Les clés sont générées automatiquement au démarrage
- Vérifiez les logs Render

### Erreur QR Code
- La génération utilise BaconQrCode avec SVG
- Pas de dépendances système supplémentaires nécessaires

### Erreur Twilio
- Vérifiez les credentials Twilio
- Le numéro `TWILIO_FROM` doit être acheté sur Twilio

## Structure du projet

```
ompay-api/
├── Dockerfile              # Configuration Docker
├── docker-entrypoint.sh    # Script de démarrage
├── render.yaml            # Configuration Render
├── composer.json          # Dépendances PHP
├── app/                   # Code Laravel
├── database/migrations/   # Migrations DB
└── storage/docs/          # Documentation Swagger
```

## Endpoints principaux

- `POST /api/auth/register` - Inscription (génère QR code)
- `POST /api/auth/login` - Connexion
- `POST /api/otp/verify` - Vérification OTP
- `GET /api/transactions` - Liste transactions
- `POST /api/transactions/depot` - Dépôt d'argent
- `GET /api/documentation` - Swagger UI

## Sécurité

- ✅ Utilisateur non-root dans Docker
- ✅ Permissions correctes sur les fichiers
- ✅ Variables d'environnement pour les secrets
- ✅ Passport pour l'authentification JWT
- ✅ QR codes générés côté serveur uniquement

Bonne chance avec le déploiement ! 🎉