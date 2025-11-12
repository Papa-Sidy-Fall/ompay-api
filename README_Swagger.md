# 📚 Documentation Swagger API OmPay

## 🚀 Accès à la documentation

### Interface Web Swagger UI
Accédez à la documentation interactive via votre navigateur :

```
http://127.0.0.1:8000/swagger.html
```

### JSON OpenAPI
Accédez directement au fichier JSON OpenAPI :

```
http://127.0.0.1:8000/api/documentation
```

**✅ La documentation est maintenant accessible !**

## 📋 Prérequis

1. **Serveur Laravel démarré** :
   ```bash
   php artisan serve
   ```

2. **Annotations Swagger** présentes dans les contrôleurs

3. **Navigateur web** pour accéder à l'interface

## 🎯 Fonctionnalités de la documentation

### 🔐 Authentification automatique
- Le token JWT est automatiquement sauvegardé après connexion
- Toutes les requêtes suivantes incluent automatiquement le header `Authorization`

### 📝 Test en temps réel
- Interface interactive pour tester tous les endpoints
- Bouton "Try it out" pour exécuter les requêtes
- Réponses en temps réel

### 📊 Structure organisée
- **Authentification** : Register, Login, OTP
- **OTP** : Envoi, vérification, renvoi
- **Transactions** : Paiement, Transfert, Dépôt, Historique
- **Distributeurs** : Liste des distributeurs

## 🔧 Endpoints documentés

### Authentification
- `POST /api/auth/register` - Inscription utilisateur
- `POST /api/auth/login` - Connexion avec OTP

### OTP (One-Time Password)
- `POST /api/otp/send` - Envoyer code OTP
- `POST /api/otp/verify` - Vérifier code OTP
- `POST /api/otp/resend` - Renvoyer code OTP

### Transactions
- `POST /api/transactions/depot` - Dépôt d'argent
- `POST /api/transactions/pay` - Effectuer un paiement
- `POST /api/transactions/transfert` - Transfert entre utilisateurs
- `POST /api/transactions/confirm` - Confirmer transaction
- `GET /api/transactions` - Historique des transactions
- `GET /api/transactions/{id}` - Détail d'une transaction

### Distributeurs
- `GET /api/distributeurs` - Liste des distributeurs

## 💡 Comment utiliser

### 1. Ouvrir la documentation
Accédez à `http://127.0.0.1:8000/swagger.html`

### 2. Tester l'inscription
1. Cliquez sur **"Authentification"** → **"Inscription utilisateur"**
2. Cliquez sur **"Try it out"**
3. Remplissez les champs :
   ```json
   {
     "nom": "Test User",
     "telephone": "771234567",
     "pin": "1234"
   }
   ```
4. Cliquez sur **"Execute"**

### 3. Tester la connexion
1. Cliquez sur **"Authentification"** → **"Connexion (envoi OTP)"**
2. Remplissez :
   ```json
   {
     "telephone": "771234567",
     "pin": "1234"
   }
   ```
3. Cliquez sur **"Execute"**
4. **Le token est automatiquement sauvegardé !**

### 4. Tester les transactions
1. Effectuez un dépôt d'abord
2. Puis testez les paiements et transferts
3. Tous les endpoints nécessitant une authentification utiliseront automatiquement le token

## 🔍 Debugging

### Codes OTP
Les codes OTP sont visibles dans les logs Laravel :
```bash
tail -f storage/logs/laravel.log | grep "Code OTP"
```

### Erreurs communes
- **401 Unauthorized** : Token manquant ou expiré
- **422 Unprocessable Entity** : Données invalides
- **404 Not Found** : Ressource inexistante

### Tokens
- Les tokens sont stockés dans le localStorage du navigateur
- Ils sont automatiquement ajoutés aux requêtes authentifiées

## 🎨 Personnalisation

### Modifier l'apparence
Le fichier `public/swagger.html` peut être personnalisé pour :
- Changer les couleurs
- Ajouter un logo
- Modifier la configuration Swagger UI

### Ajouter des endpoints
1. Ajoutez les annotations `@OA\*` dans vos contrôleurs
2. Redémarrez le serveur
3. La documentation se met à jour automatiquement

## 📱 Formats de réponse

Toutes les réponses suivent ce format standard :

**✅ Succès** :
```json
{
  "succes": true,
  "message": "Opération réussie",
  "donnees": { ... }
}
```

**❌ Erreur** :
```json
{
  "succes": false,
  "message": "Description de l'erreur",
  "erreurs": { ... }
}
```

---

**🎉 Votre documentation Swagger est maintenant accessible et interactive !**