# 🧪 Guide de test API OmPay avec Postman

## 📋 Prérequis

1. **Laravel serveur démarré** :
   ```bash
   php artisan serve
   ```

2. **Base de données configurée** avec les tables créées

3. **Postman installé** sur votre machine

## 📥 Import de la collection

1. Ouvrez Postman
2. Cliquez sur **"Import"** (bouton en haut à gauche)
3. Sélectionnez **"File"**
4. Choisissez le fichier `OmPay_Postman_Collection.json`
5. Cliquez sur **"Import"**

## 🔧 Configuration des variables

Dans Postman, allez dans votre collection **"OmPay API - Collection de test"** :

1. Cliquez sur les **"..."** à côté du nom de la collection
2. Sélectionnez **"Edit"**
3. Allez dans l'onglet **"Variables"**
4. Configurez :
   - `base_url` : `http://127.0.0.1:8000` (ou votre URL)
   - `token` : laissez vide (sera rempli automatiquement)
   - `transaction_id` : laissez vide (sera rempli automatiquement)

## 🚀 Guide de test étape par étape

### Étape 1 : Inscription d'un utilisateur

**Requête** : `POST /api/auth/register`

**Body** :
```json
{
  "nom": "Test User",
  "telephone": "771157773",
  "pin": "1234"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Utilisateur créé avec succès",
  "donnees": {
    "utilisateur": {...},
    "token": "..."
  }
}
```

### Étape 2 : Connexion (envoi OTP)

**Requête** : `POST /api/auth/login`

**Body** :
```json
{
  "telephone": "771157773",
  "pin": "1234"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Veuillez vérifier votre téléphone avec le code OTP",
  "donnees": {
    "utilisateur": {...},
    "message_complementaire": "Un code OTP a été envoyé à votre numéro de téléphone"
  }
}
```

### Étape 3 : Récupérer le code OTP

**Dans les logs Laravel** :
```bash
tail -n 10 storage/logs/laravel.log | grep "Code OTP"
```

**Exemple de log** :
```
[2025-11-11 09:40:45] local.INFO: Code OTP (SMS failed) pour 771157773 (connexion): 0939
```

**Code OTP** : `0939`

### Étape 4 : Vérifier le code OTP

**Requête** : `POST /api/otp/verify`

**Body** :
```json
{
  "telephone": "771157773",
  "code": "0939",
  "type": "connexion"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Code OTP vérifié avec succès",
  "donnees": {
    "telephone": "771157773",
    "type": "connexion",
    "verifie": true,
    "utilisateur": {...},
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
  }
}
```

**✅ Le token est automatiquement sauvegardé dans les variables de collection !**

### Étape 5 : Créditer le compte (Dépôt)

**Requête** : `POST /api/transactions/depot`

**Headers** :
- `Authorization: Bearer {{token}}`

**Body** :
```json
{
  "montant": 100.00,
  "description": "Dépôt initial"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Dépôt effectué avec succès",
  "donnees": {
    "nouveau_solde": "100.00",
    "montant_depose": 100
  }
}
```

### Étape 6 : Effectuer un paiement

**Requête** : `POST /api/transactions/pay`

**Headers** :
- `Authorization: Bearer {{token}}`

**Body** :
```json
{
  "montant": 50.00,
  "description": "Paiement test"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Code OTP envoyé pour confirmer la transaction",
  "donnees": {
    "transaction_id": "894fc500-b74d-4ede-891e-5f54b3f1ce6a",
    "montant": 50,
    "description": "Paiement test",
    "otp_required": true,
    "expire_at": "2025-11-11T09:56:29.000000Z"
  }
}
```

**✅ L'ID de transaction est automatiquement sauvegardé !**

### Étape 7 : Confirmer le paiement

**Récupérer le code OTP dans les logs** :
```bash
tail -n 5 storage/logs/laravel.log | grep "transaction"
```

**Exemple** : `0169`

**Requête** : `POST /api/transactions/confirm`

**Headers** :
- `Authorization: Bearer {{token}}`

**Body** :
```json
{
  "transaction_id": "{{transaction_id}}",
  "code": "0169"
}
```

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Transaction confirmée avec succès"
}
```

### Étape 8 : Vérifier l'historique

**Requête** : `GET /api/transactions`

**Headers** :
- `Authorization: Bearer {{token}}`

**Réponse attendue** :
```json
{
  "succes": true,
  "message": "Opération réussie",
  "donnees": {
    "transactions": [
      {
        "uuid": "...",
        "type": "paiement",
        "montant": "-50.00",
        "description": "Paiement confirmé",
        "created_at": "..."
      },
      {
        "uuid": "...",
        "type": "depot",
        "montant": "100.00",
        "description": "Dépôt initial",
        "created_at": "..."
      }
    ]
  }
}
```

## 🔄 Tests supplémentaires

### Transfert entre utilisateurs

1. **Créer un deuxième utilisateur** avec un téléphone différent
2. **Se connecter** avec le deuxième utilisateur
3. **Effectuer un transfert** :
   ```json
   {
     "destinataire_uuid": "UUID_DU_PREMIER_UTILISATEUR",
     "montant": 25.00,
     "description": "Transfert test"
   }
   ```

### Renvoyer un code OTP

**Requête** : `POST /api/otp/resend`

**Body** :
```json
{
  "telephone": "771157773",
  "type": "connexion"
}
```

## 🐛 Dépannage

### Erreur "Personal access client not found"
```bash
php artisan passport:install --force
```

### Erreur "Code OTP invalide"
- Vérifiez les logs Laravel pour le code exact
- Les codes expirent après 5 minutes

### Erreur "Solde insuffisant"
- Effectuez d'abord un dépôt pour créditer le compte

### SMS ne fonctionne pas
- En développement, les codes sont loggés dans `storage/logs/laravel.log`
- Configurez Twilio pour la production

## 📊 Structure des réponses API

Toutes les réponses suivent ce format :

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

## 🎯 Endpoints disponibles

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion (OTP) |
| POST | `/api/otp/verify` | Vérification OTP |
| POST | `/api/otp/resend` | Renvoi OTP |
| POST | `/api/transactions/depot` | Dépôt d'argent |
| POST | `/api/transactions/pay` | Paiement |
| POST | `/api/transactions/transfert` | Transfert |
| POST | `/api/transactions/confirm` | Confirmation transaction |
| GET | `/api/transactions` | Historique |
| GET | `/api/transactions/{id}` | Détail transaction |
| GET | `/api/distributeurs` | Liste distributeurs |

---

**🎉 Votre API OmPay est maintenant prête à être testée !**