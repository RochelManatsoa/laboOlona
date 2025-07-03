<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RegionAgenceSubscriber implements EventSubscriberInterface
{
    private const AGENCIES_BY_REGION = [
        'Diana' => [
            'Antsiranana',
            'Nosy-Be',
            'Ambanja',
            'Ambilobe',
            'Tanambao',
        ],
        'Sava' => [
            'Sambava',
            'Antalaha',
            'Vohémar',
            'Andapa',
        ],
        'Itasy' => [
            'Miarinarivo',
            'Arivonimamo',
        ],
        'Analamanga' => [
            'Antsahavola',
            'Ambohibao',
            'Ankadimbahoaka',
            'Ankorondrano',
            'Ambatobe',
            'Andraharo',
            'Behoririka',
            'Ampasampito',
            'Analamahitsy',
            'Itaosy',
            'Tsimbazaza',
            'Ivandry',
            'Andavamamba',
            'Mahitsy',
            'Andravoahangy',
            'Mahazo',
            'Ivato',
            'Anosizato',
            'Sabotsy Namehana',
        ],
        'Vakinankaratra' => [
            'Antsirabe',
            'Ambatolampy',
            'Betafo',
            'Ambohimiandrisoa',
        ],
        'Bongolava' => [
            'Tsiroanomandidy',
            'Analavory',
        ],
        'Sofia' => [
            'Antsohihy',
            'Port Bergé',
            'Mampikony',
        ],
        'Boeny' => [
            'Mahajanga',
            'Tsaramandroso',
            'Marovoay',
            'Maevatanàna',
            'Port Bergé',
        ],
        'Betsiboka' => [
            'Maevatanana',
        ],
        'Melaky' => [
            'Maintirano',
        ],
        'Alaotra-Mangoro' => [
            'Ambatondrazaka',
            'Moramanga',
            'Amparafaravola',
            'Tanambe',
        ],
        'Atsinanana' => [
            'Toamasina',
            'Fénérive-Est',
            'Mahanoro',
            'Brickaville',
        ],
        'Analanjirofo' => [
            'Fénérive-Est',
            'Maroantsetra',
        ],
        'Amoron\'i Mania' => [
            'Ambositra',
        ],
        'Haute Matsiatra' => [
            'Fianarantsoa',
            'Ambalavao',
            'Ambohimahasoa',
        ],
        'Vatovavy-Fitovinany' => [
            'Manakara',
            'Mananjary',
        ],
        'Atsimo-Atsinanana' => [
            'Farafangana',
            'Vangaindrano',
        ],
        'Ihorombe' => [
            'Ihosy',
        ],
        'Menabe' => [
            'Morondava',
            'Miandrivazo',
        ],
        'Atsimo-Andrefana' => [
            'Toliara',
            'Ilakaka',
            'Tanambao II',
            'Morombe',
            'Sakaraha',
        ],
        'Androy' => [
            'Ambovombe',
        ],
        'Anosy' => [
            'Tolagnaro',
        ],
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'updateLieuField',
            FormEvents::PRE_SUBMIT => 'updateLieuField',
        ];
    }

    public function updateLieuField(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $region = null;

        if (is_array($data)) {
            $region = $data['region'] ?? null;
        } elseif (is_object($data) && method_exists($data, 'getRegion')) {
            $region = $data->getRegion();
        }

        $agencies = self::AGENCIES_BY_REGION[$region] ?? [];

        $form->add('lieu', ChoiceType::class, [
            'choices' => array_combine($agencies, $agencies),
            'placeholder' => 'Sélectionnez une agence',
            'required' => false,
            'help' => 'Ce champ sera mis à jour automatiquement selon la région sélectionnée.',
            'label' => 'Agence',
            'label_attr' => ['class' => 'fw-bold fs-6'],
        ]);
    }
}
