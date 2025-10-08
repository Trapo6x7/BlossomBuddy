<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Plant;
use Carbon\Carbon;

class WateringReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public $plant;
    public $userPlantInfo;
    public $wateringSchedule;
    public $weatherData;

    /**
     * Create a new notification instance.
     */
    public function __construct($plant, $userPlantInfo, $wateringSchedule, $weatherData)
    {
        $this->plant = $plant;
        $this->userPlantInfo = $userPlantInfo;
        $this->wateringSchedule = $wateringSchedule;
        $this->weatherData = $weatherData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $plantName = $this->plant['common_name'];
        $city = $this->userPlantInfo['city'];
        $daysUntilWatering = $this->wateringSchedule['days_until_watering'];
        $hoursUntilWatering = $this->wateringSchedule['hours_until_watering'];
        $temperature = $this->weatherData['current']['temp_c'] ?? 'N/A';
        $humidity = $this->weatherData['current']['humidity'] ?? 'N/A';
        $condition = $this->weatherData['current']['condition']['text'] ?? 'N/A';
        $watering = $this->plant['watering'] ?? 'medium';
        $wateringTip = $this->getWateringTip();
        
        // Définir le sujet selon l'urgence
        $subject = '';
        if ($hoursUntilWatering <= 0) {
            $subject = "🚨 Urgent : {$plantName} a besoin d'eau maintenant !";
        } elseif ($hoursUntilWatering <= 24) {
            $subject = "⏰ Rappel : {$plantName} à arroser aujourd'hui";
        } else {
            $subject = "🌱 Rappel : {$plantName} à arroser bientôt";
        }

        return (new MailMessage)
            ->subject($subject)
            ->markdown('emails.watering-reminder', [
                'notifiable' => $notifiable,
                'plantName' => $plantName,
                'city' => $city,
                'daysUntilWatering' => $daysUntilWatering,
                'hoursUntilWatering' => $hoursUntilWatering,
                'temperature' => $temperature,
                'humidity' => $humidity,
                'condition' => $condition,
                'watering' => $watering,
                'wateringTip' => $wateringTip,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plant_id' => $this->plant['id'],
            'plant_name' => $this->plant['common_name'],
            'city' => $this->userPlantInfo['city'],
            'hours_until_watering' => $this->wateringSchedule['hours_until_watering'],
            'weather_condition' => $this->weatherData['current']['condition']['text'] ?? null,
            'sent_at' => Carbon::now()->toISOString(),
        ];
    }

    /**
     * Get personalized watering tip based on plant and weather
     */
    private function getWateringTip(): string
    {
        $humidity = $this->weatherData['current']['humidity'] ?? 50;
        $temp = $this->weatherData['current']['temp_c'] ?? 20;
        $watering = $this->plant['watering'] ?? 'medium';

        if ($humidity > 70) {
            return "L'humidité est élevée, vous pouvez espacer un peu l'arrosage.";
        } elseif ($humidity < 40) {
            return "L'air est sec, votre plante pourrait avoir besoin d'un peu plus d'eau.";
        } elseif ($temp > 25 && $watering === 'high') {
            return "Il fait chaud et votre plante aime l'eau, surveillez la terre pour qu'elle reste humide.";
        } elseif ($temp < 15) {
            return "Il fait frais, votre plante a besoin de moins d'eau qu'en été.";
        } else {
            return "Arrosez quand la surface du sol commence à sécher.";
        }
    }
}
