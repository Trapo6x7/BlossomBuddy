@component('mail::message')
# 🌱 Rappel d'arrosage BlossomBuddy

Bonjour {{ $notifiable->prenom }} !

@if($hoursUntilWatering <= 0)
@component('mail::panel')
🚨 **URGENT : Arrosage immédiat nécessaire**

Votre **{{ $plantName }}** à {{ $city }} a besoin d'eau maintenant !
@endcomponent
@elseif($hoursUntilWatering <= 24)
@component('mail::panel')
⏰ **Arrosage aujourd'hui**

Votre **{{ $plantName }}** à {{ $city }} doit être arrosé dans {{ $hoursUntilWatering }}h.
@endcomponent
@else
@component('mail::panel')
🌱 **Arrosage à prévoir**

Votre **{{ $plantName }}** à {{ $city }} devra être arrosé dans {{ $daysUntilWatering }} jour(s).
@endcomponent
@endif

## 📊 Informations de votre plante

@component('mail::table')
| Élément | Détail |
| ------- | ------ |
| 🌿 **Plante** | {{ $plantName }} |
| 📍 **Ville** | {{ $city }} |
| ⏰ **Arrosage dans** | {{ $daysUntilWatering > 0 ? $daysUntilWatering . ' jour(s)' : $hoursUntilWatering . 'h' }} |
| 💧 **Type d'arrosage** | {{ ucfirst($watering) }} |
@endcomponent

## 🌤️ Conditions météo actuelles

@component('mail::table')
| Météo | Valeur |
| ----- | ------ |
| 🌡️ **Température** | {{ $temperature }}°C |
| 💧 **Humidité** | {{ $humidity }}% |
| ☁️ **Conditions** | {{ $condition }} |
@endcomponent

## 💡 Conseil personnalisé

{{ $wateringTip }}

@component('mail::button', ['url' => url('/user/plants')])
Voir mes plantes
@endcomponent

---

**BlossomBuddy** - Votre assistant personnel pour prendre soin de vos plantes 🌱

*Vous recevez cet email car vous avez configuré des rappels d'arrosage dans BlossomBuddy.*

@endcomponent