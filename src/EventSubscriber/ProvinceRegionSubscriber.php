<?php 
// src/Form/EventSubscriber/ProvinceRegionSubscriber.php
namespace App\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProvinceRegionSubscriber implements EventSubscriberInterface
{
    private const REGIONS_BY_PROVINCE = [
        'Antananarivo' => ['Analamanga', 'Vakinankaratra', 'Bongolava'],
        'Fianarantsoa' => ['Haute Matsiatra', 'Amoron’i Mania', 'Atsimo-Atsinanana', 'Ihorombe', 'Vatovavy'],
        'Toamasina' => ['Alaotra-Mangoro', 'Atsinanana', 'Analanjirofo'],
        'Mahajanga' => ['Boeny', 'Betsiboka', 'Sofia'],
        'Toliara' => ['Atsimo-Andrefana', 'Androy', 'Anosy', 'Menabe'],
        'Antsiranana' => ['Diana', 'Sava'],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'updateRegionField',
            FormEvents::PRE_SUBMIT => 'updateRegionField'
        ];
    }

    public function updateRegionField(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $province = null;

        if (is_array($data)) {
            $province = $data['province'] ?? null;
        } elseif (is_object($data) && method_exists($data, 'getProvince')) {
            $province = $data->getProvince();
        }

        $regions = self::REGIONS_BY_PROVINCE[$province] ?? [];

        $form->add('region', ChoiceType::class, [
            'choices' => array_combine($regions, $regions),
            'placeholder' => 'Sélectionnez une région',
            'required' => false,
            'help' => 'Ce champ sera mis à jour automatiquement selon la province sélectionnée.',
            'label_attr' => ['class' => 'fw-bold fs-5'],
        ]);
    }
}
