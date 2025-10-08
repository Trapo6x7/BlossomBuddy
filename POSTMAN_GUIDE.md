# 🌱 BlossomBuddy API - Guide d'utilisation Postman

## 📋 Vue d'ensemble

Cette API permet de gérer un système intelligent d'arrosage de plantes avec intégration météorologique et calculs automatisés.

## 🚀 Configuration rapide

### 1. **Importer la collection dans Postman**
1. Ouvrir Postman
2. Cliquer sur **Import**
3. Sélectionner le fichier `BlossomBuddy_Postman_Collection.json`
4. Importer également `BlossomBuddy_Environment.json` comme environnement

### 2. **Configurer l'environnement**
- Sélectionner l'environnement "BlossomBuddy Environment"
- Vérifier que `base_url` = `http://localhost:8000/api`
- Les autres variables seront remplies automatiquement

### 3. **Démarrer le serveur Laravel**
```bash
cd C:\Users\Utilisateur\Documents\BlossomBuddy\BlossomBuddy
php artisan serve
```

## 🔐 Authentification

### **Workflow d'authentification**
1. **Créer un compte** : `POST /register`
2. **Se connecter** : `POST /login` 
   - ✅ Le token sera automatiquement sauvegardé dans `{{auth_token}}`
3. **Utiliser les endpoints protégés** avec le token Bearer

### **Exemple de création de compte**
```json
{
    "prenom": "John",
    "nom": "Doe", 
    "email": "john.doe@example.com",
    "password": "password123"
}
```

## 🌱 Gestion des plantes

### **Créer une plante**
```json
{
    "common_name": "Ficus",
    "ville": "Paris",
    "watering": "medium",
    "watering_general_benchmark": {
        "value": "7-10", 
        "unit": "days"
    }
}
```

### **Ajouter une plante à un utilisateur**
```json
{
    "plant_name": "Ficus",
    "city": "Paris"
}
```

## 💧 Système d'arrosage intelligent ⭐️ SIMPLIFIÉ

### **Workflow simple en 3 étapes**

### **1. Ajouter une plante (avec watering automatique)**
**Endpoint** : `POST /user/plant`
```json
{
    "plant_name": "Ficus",
    "city": "Paris",
    "watering_preferences": {
        "frequency": "normal"
    }
}
```

**Réponse** :
```json
{
    "message": "Plante ajoutée avec succès !",
    "plant": {
        "id": 1,
        "common_name": "Ficus",
        "watering": "medium",
        "city": "Paris",
        "last_watered_at": "2025-10-08T10:00:00Z"
    },
    "next_steps": {
        "dashboard": "GET /user/plants - Voir toutes vos plantes",
        "watering": "POST /plant/water - Enregistrer un arrosage"
    }
}
```

### **2. Voir le tableau de bord complet** ✅ AVEC WATERING SCHEDULE
**Endpoint** : `GET /user/plants`

**Réponse complète** :
```json
[
    {
        "plant": {
            "id": 1,
            "common_name": "Ficus",
            "scientific_name": "Ficus benjamina",
            "watering": "medium",
            "watering_general_benchmark": {"value": "7-10", "unit": "days"}
        },
        "user_plant_info": {
            "city": "Paris",
            "last_watered_at": "2025-10-08T10:00:00Z",
            "watering_preferences": null,
            "created_at": "2025-10-08T10:00:00Z",
            "updated_at": "2025-10-08T10:00:00Z"
        },
        "watering_schedule": {
            "next_watering_date": "2025-10-15T10:00:00Z",
            "hours_until_watering": 168,
            "days_until_watering": 7,
            "watering_frequency_days": 7,
            "weather_adjustment": "normal",
            "recommendation": "Arroser dans 7 jours"
        },
        "weather_data": {
            "temperature": 22,
            "humidity": 65,
            "condition": "Partly cloudy"
        }
    }
]
```

**Filtrage par ville** : `GET /user/plants?city=Paris`
**Tri automatique** : Les plantes sont triées par urgence d'arrosage

### **3. Enregistrer un arrosage**
**Endpoint** : `POST /plant/water`
```json
{
    "plant_id": 1,
    "city": "Paris"
}
```

- Met à jour automatiquement `last_watered_at`
- Nouveau programme visible dans `GET /user/plants`

## 🎯 Workflow recommandé

### **Scénario complet : Nouveau utilisateur** ⭐️ SIMPLIFIÉ

1. **S'inscrire** → `POST /register`
2. **Se connecter** → `POST /login`
3. **Ajouter une plante (avec watering auto)** → `POST /user/plant`
4. **Voir le tableau de bord** → `GET /user/plants`
5. **Arroser la plante** → `POST /plant/water`
6. **Voir le programme mis à jour** → `GET /user/plants`

### **Tests utiles**
- **Dashboard complet** : `GET /user/plants`
- **Filtrage par ville** : `GET /user/plants?city=Paris`
- **Enregistrer arrosage** : `POST /plant/water`
- **Voir toutes les plantes** : `GET /plant`

## 📊 Documentation interactive

**URL** : http://localhost:8000/api/documentation

La documentation Swagger est disponible avec :
- ✅ Interface interactive pour tester
- ✅ Schémas de requêtes/réponses
- ✅ Authentification intégrée
- ✅ Tous les nouveaux endpoints

## 🔧 Variables d'environnement Postman

| Variable | Description | Exemple |
|----------|-------------|---------|
| `{{base_url}}` | URL de base de l'API | `http://localhost:8000/api` |
| `{{auth_token}}` | Token d'authentification | Auto-rempli après login |
| `{{user_email}}` | Email pour les tests | `john.doe@example.com` |
| `{{user_password}}` | Mot de passe | `password123` |

## ⚡ Fonctionnalités avancées

### **Gestion intelligente des dates**
- Si `last_watered_at` est null, utilise la date actuelle
- Calculs basés sur la météo en temps réel
- Ajustements automatiques selon l'humidité

### **Tri automatique**
- Les plantes sont triées par urgence d'arrosage
- Les plus urgentes apparaissent en premier

### **Intégration météo**
- Données en temps réel via WeatherAPI
- Cache intelligent pour optimiser les performances
- Ajustements automatiques des recommandations

## 🐛 Dépannage

### **Erreur 401 Unauthorized**
- Vérifier que le token est bien configuré
- Re-faire un login si nécessaire

### **Erreur 422 Validation**
- Vérifier le format JSON
- Contrôler les champs requis

### **Erreur 500 Server Error**
- Vérifier que le serveur Laravel est démarré
- Consulter les logs : `storage/logs/laravel.log`

---

🎉 **Votre API BlossomBuddy est prête à l'emploi !**