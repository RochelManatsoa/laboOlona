<?php

namespace App\Twig;

use DateTime;
use DateInterval;
use App\Entity\User;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Availability;
use App\Entity\Candidate\CV;
use App\Entity\Notification;
use App\Entity\CandidateProfile;
use App\Entity\EntrepriseProfile;
use App\Entity\Moderateur\EditedCv;
use App\Entity\Entreprise\JobListing;
use Twig\Extension\AbstractExtension;
use App\Entity\Candidate\Applications;
use App\Entity\Moderateur\Assignation;
use App\Entity\Candidate\TarifCandidat;
use App\Entity\Finance\Devise;
use App\Entity\Moderateur\Forfait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\Entreprise\JobListingRepository;
use App\Repository\Moderateur\AssignationRepository;
use App\Repository\NotificationRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        private EntityManagerInterface $em,
        private AssignationRepository $assignationRepository,
        private JobListingRepository $jobListingRepository,
        private NotificationRepository $notificationRepository,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_label', [$this, 'statusLabel']),
            new TwigFilter('posting_status_Label', [$this, 'postingStatusLabel']),
            new TwigFilter('candidature_status_Label', [$this, 'candidatureStatusLabel']),
            new TwigFilter('time_ago', [$this, 'timeAgo']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('meta_title', [$this, 'metaTitle']),
            new TwigFunction('dashboard_title', [$this, 'dashboardTitle']),
            new TwigFunction('identity_title', [$this, 'identityTitle']),
            new TwigFunction('meta_description', [$this, 'metaDescription']),
            new TwigFunction('meta_keywords', [$this, 'metaKeywords']),
            new TwigFunction('filterContent', [$this, 'filterContent']),
            new TwigFunction('doShortcode', [$this, 'doShortcode']),
            new TwigFunction('isoToEmoji', [$this, 'isoToEmoji']),
            new TwigFunction('getEditedCv', [$this, 'getEditedCv']),
            new TwigFunction('safeFileName', [$this, 'safeFileName']),
            new TwigFunction('show_country', [$this, 'showCountry']),
            new TwigFunction('experience_text', [$this, 'getExperienceText']),
            new TwigFunction('date_difference', [$this, 'dateDifference']),
            new TwigFunction('years_difference', [$this, 'yearsDifference']),
            new TwigFunction('status_label', [$this, 'statusLabel']),
            new TwigFunction('account_label', [$this, 'accountLabel']),
            new TwigFunction('formatTimeDiff', [$this, 'formatTimeDiff']),
            new TwigFunction('formatDuration', [$this, 'formatDuration']),
            new TwigFunction('getStatuses', [$this, 'getStatuses']),
            new TwigFunction('getEntrepriseAnnonceByCandidat', [$this, 'getEntrepriseAnnonceByCandidat']),
            new TwigFunction('checkAvailability', [$this, 'checkAvailability']),
            new TwigFunction('getAge', [$this, 'getAge']),
            new TwigFunction('getPseudo', [$this, 'getPseudo']),
            new TwigFunction('invitation', [$this, 'invitation']),
            new TwigFunction('getTarifForfait', [$this, 'getTarifForfait']),
            new TwigFunction('getTarifCandidat', [$this, 'getTarifCandidat']),
            new TwigFunction('generateReference', [$this, 'generateReference']),
            new TwigFunction('generatePseudo', [$this, 'generatePseudo']),
            new TwigFunction('getForfaitAssignation', [$this, 'getForfaitAssignation']),
            new TwigFunction('getStatusAssignation', [$this, 'getStatusAssignation']),
            new TwigFunction('getTypeAssignation', [$this, 'getTypeAssignation']),
            new TwigFunction('getAssignByEntreprise', [$this, 'getAssignByEntreprise']),
            new TwigFunction('findValidJobListing', [$this, 'findValidJobListing']),
            new TwigFunction('findPendingJobListing', [$this, 'findPendingJobListing']),
            new TwigFunction('countUnReadNotification', [$this, 'countUnReadNotification']),
            new TwigFunction('tailleEntreprise', [$this, 'tailleEntreprise']),
        ];
    }



    public function getStatuses(string $status = NULL): string
    {
        $statuses = [
            JobListing::STATUS_DRAFT => 'Bruillon',
            JobListing::STATUS_PUBLISHED => 'Publiée',
            JobListing::STATUS_PENDING => 'En attente de modération',
            JobListing::STATUS_REJECTED => 'Rejetée',
            JobListing::STATUS_EXPIRED => 'Expirée',
            JobListing::STATUS_ARCHIVED => 'Archivée',
            JobListing::STATUS_UNPUBLISHED => 'Non publiée',
            JobListing::STATUS_DELETED => 'Effacée',
            JobListing::STATUS_FEATURED => 'Mis en avant',
            JobListing::STATUS_RESERVED => 'Réservée',
        ];

        return $statuses[$status];
    }

    public function tailleEntreprise(string $taille = NULL): string
    {
        $tailles = [
            EntrepriseProfile::SIZE_SMALL => 'Petite (1-10 employés)',
            EntrepriseProfile::SIZE_MEDIUM => 'Moyenne (11-100 employés)',
            EntrepriseProfile::SIZE_LARGE => 'Grande (plus de 100 employés)',
        ];

        return $tailles[$taille];
    }

    public function getEntrepriseStatuses(string $status = NULL): string
    {
        $statuses = [
            EntrepriseProfile::STATUS_VALID => 'Valide',
            EntrepriseProfile::STATUS_PREMIUM => 'Premium',
            EntrepriseProfile::STATUS_PENDING => 'En attente',
            EntrepriseProfile::STATUS_BANNED => 'Banni',
        ];

        return $statuses[$status];
    }

    public function getEntrepriseAnnonceByCandidat(EntrepriseProfile $entreprise, CandidateProfile $candidat): ?JobListing
    {
        foreach ($entreprise->getJobListings() as $jobListing) {
            if ($this->isSuitableForCandidat($jobListing, $candidat)) {
                return $jobListing;
            }
        }

        return null;
    }

    private function isSuitableForCandidat(JobListing $jobListing, CandidateProfile $candidat): bool
    {
        // Vérifier si le candidat a déjà postulé à cette annonce
        foreach ($candidat->getApplications() as $application) {
            if ($application->getAnnonce() === $jobListing) {
                // Le candidat a déjà postulé à cette annonce
                return true;
            }
        }

        return false;
    }

    public function accountLabel(string $account): string
    {
        switch ($account) {
            case User::ACCOUNT_CANDIDAT :
                return 'CANDIDAT';
            case User::ACCOUNT_ENTREPRISE :
                return 'ENTREPRISE';
            default:
                return 'COOPTEUR';
        }
    }

    public function statusLabel(string $status)
    {
        $labels = [
            JobListing::STATUS_DRAFT  => 'Bruillon',
            JobListing::STATUS_PUBLISHED  => 'Publiée',
            JobListing::STATUS_PENDING  => 'En attente',
            JobListing::STATUS_REJECTED  => 'Rejetée',
            JobListing::STATUS_EXPIRED  => 'Expirée',
            JobListing::STATUS_ARCHIVED  => 'Archivée',
            JobListing::STATUS_UNPUBLISHED  => 'Non publiée',
            JobListing::STATUS_DELETED  => 'Effacée',
            JobListing::STATUS_FEATURED  => 'Mis en avant',
            JobListing::STATUS_RESERVED  => 'Réservée',
        ];

        return $labels[$status];
    }

    public function candidatureStatusLabel(string $status)
    {
        $labels = [
            Applications::STATUS_PENDING  => 'En cours',
            Applications::STATUS_REJECTED  => 'Non retenues',
            Applications::STATUS_ACCEPTED  => 'Acceptée',
            Applications::STATUS_ARCHIVED  => 'Archivée',
            Applications::STATUS_METTING  => 'Rendez-vous',
        ];

        return $labels[$status];
    }

    public function metaTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 

        return $this->translator->trans($routeName . '.title');
    }

    public function dashboardTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        
        /** @var User $user */
        $user = $this->security->getUser();
        $name = $user->getPrenom();
        if($user->getEntrepriseProfile() instanceof EntrepriseProfile){
            $name = $user->getEntrepriseProfile()->getNom();
        }

        return $this->translator->trans($routeName . '.dashboard_title', ['%company_name%' => $name]);
    }

    public function identityTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        $user = $this->security->getUser();

        return $this->translator->trans($routeName . '.identity_title');
    }

    public function metaDescription(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        return $this->translator->trans($routeName . '.description');
    }

    public function metaKeywords(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route');  
        return $this->translator->trans($routeName . '.keywords');
    }

    public function doShortcode($content)
    {
        // Liste des shortcodes à supprimer
            $shortcodesToRemove = [
                'et_pb_wc_breadcrumb',
                'et_pb_wc_title',
                'et_pb_wc_rating',
                'et_pb_wc_cart_notice',
                'dsm_typing_effect',
                'et_pb_wc_description',
                'et_pb_wc_add_to_cart',
                'et_pb_wc_images',
                'et_pb_wc_tabs',
                'et_pb_wc_related_products',
                'et_pb_wc_upsells',
                'et_pb_wc_price',
                'et_pb_wc_meta'
            ];

            foreach ($shortcodesToRemove as $shortcode) {
                // Supprime les shortcodes et leurs contenus / attributs
                $content = preg_replace('/\[' . $shortcode . '.*?\](\[\/' . $shortcode . '\])?/', '', $content);
            }

            $content = preg_replace_callback('/\[et_pb_section(.*?)\]/', function($matches) {
                // Analyser et transformer les attributs
                $attributes = $matches[1];
                $style = '';

                // Exemple de traitement des attributs
                if (preg_match('/fb_built="(.*?)"/', $attributes, $fbBuiltMatches)) {
                    // Traiter l'attribut fb_built ici si nécessaire
                }
                if (preg_match('/_builder_version="(.*?)"/', $attributes, $builderVersionMatches)) {
                    // Traiter l'attribut _builder_version ici si nécessaire
                }
                if (preg_match('/background_color="(.*?)"/', $attributes, $bgColorMatches)) {
                    $style .= 'background-color:' . strtolower($bgColorMatches[1]) . ';';
                }
                if (preg_match('/custom_padding="(.*?)"/', $attributes, $customPaddingMatches)) {
                    $style .= 'padding:' . $customPaddingMatches[1] . ';';
                }

                return '<div class="et-pb-section" style="' . $style . '">';
            }, $content);

            // Assurez-vous de fermer la balise div ouverte pour chaque section
            $content = str_replace('[/et_pb_section]', '</div>', $content);


            $content = preg_replace_callback('/\[et_pb_row(.*?)\]/', function($matches) {
                // Analyser et transformer les attributs
                $attributes = $matches[1];
                $style = '';
                $class = 'row'; // Classe Bootstrap pour les rangées

                // Exemple de traitement des attributs
                if (preg_match('/_builder_version="(.*?)"/', $attributes, $builderVersionMatches)) {
                    // Traiter l'attribut _builder_version ici si nécessaire
                }
                if (preg_match('/background_size="(.*?)"/', $attributes, $backgroundSizeMatches)) {
                    // Traiter l'attribut background_size ici si nécessaire
                }
                if (preg_match('/background_position="(.*?)"/', $attributes, $backgroundPositionMatches)) {
                    // Traiter l'attribut background_position ici si nécessaire
                }
                if (preg_match('/background_repeat="(.*?)"/', $attributes, $backgroundRepeatMatches)) {
                    // Traiter l'attribut background_repeat ici si nécessaire
                }
                if (preg_match('/width="(.*?)"/', $attributes, $widthMatches)) {
                    $style .= 'width:' . $widthMatches[1] . ';';
                }
                if (preg_match('/custom_padding="(.*?)"/', $attributes, $customPaddingMatches)) {
                    $style .= 'padding:' . $customPaddingMatches[1] . ';';
                }

                return '<div class="' . $class . '" style="' . $style . '">';
            }, $content);

            // Assurez-vous de fermer la balise div ouverte pour chaque rangée
            $content = str_replace('[/et_pb_row]', '</div>', $content);


            $content = preg_replace_callback('/\[et_pb_column type="([^"]+)"(.*?)\]/', function($matches) {
                // Analyser et transformer les attributs
                $type = $matches[1];
                $otherAttributes = $matches[2];
                $style = '';
                $class = '';

                // Convertir le type Divi en classe Bootstrap
                if ($type === '4_4') {
                    $class = 'col-12'; // Colonne pleine largeur dans Bootstrap
                }
                // Ajouter d'autres correspondances de type ici si nécessaire

                // Traitement des autres attributs
                if (preg_match('/_builder_version="(.*?)"/', $otherAttributes, $builderVersionMatches)) {
                    // Traiter l'attribut _builder_version ici si nécessaire
                }
                if (preg_match('/custom_padding="(.*?)"/', $otherAttributes, $customPaddingMatches)) {
                    $style .= 'padding:' . $customPaddingMatches[1] . ';';
                }

                return '<div class="' . $class . '" style="' . $style . '">';
            }, $content);

            // Assurez-vous de fermer la balise div ouverte pour chaque colonne
            $content = str_replace('[/et_pb_column]', '</div>', $content);


            $content = preg_replace_callback('/\[et_pb_video src="([^"]+)"(.*?)\](\[\/et_pb_video\])?/', function($matches) {
                $videoUrl = $matches[1];
                $otherAttributes = $matches[2];
        
                // Convertir l'URL YouTube en URL d'intégration si nécessaire
                $embedUrl = str_replace("watch?v=", "embed/", $videoUrl);
        
                // Vous pouvez extraire et utiliser d'autres attributs ici si nécessaire
                // Par exemple, pour l'image de prévisualisation (image_src)
                if (preg_match('/image_src="([^"]+)"/', $otherAttributes, $imageSrcMatches)) {
                    $imageSrc = $imageSrcMatches[1];
                    // Utiliser $imageSrc pour un poster ou une image de prévisualisation, si nécessaire
                }
        
                // Retourner un élément iframe pour l'intégration de la vidéo
                return '<section class="ratio ratio-16x9 "><iframe src="' . $embedUrl . '" frameborder="0" allowfullscreen></iframe></section>';
            }, $content);


            $content = preg_replace_callback('/\[et_pb_text(.*?)\]/', function($matches) {
                $attributes = $matches[1];
                $style = '';

                // Exemple de traitement des attributs pour extraire les styles
                if (preg_match('/text_font_size="(.*?)"/', $attributes, $fontSizeMatches)) {
                    $style .= 'font-size:' . $fontSizeMatches[1] . ';';
                }
                if (preg_match('/text_line_height="(.*?)"/', $attributes, $lineHeightMatches)) {
                    $style .= 'line-height:' . $lineHeightMatches[1] . ';';
                }
                if (preg_match('/header_3_font_size="(.*?)"/', $attributes, $h3FontSizeMatches)) {
                    // Vous pouvez choisir d'appliquer ou d'ignorer ce style
                    // $style .= 'font-size:' . $h3FontSizeMatches[1] . ' for h3 headers;';
                }

                // Retourner une balise div ou p avec les styles appliqués
                return '<div style="' . $style . '" class="text-white">';
            }, $content);

            // Assurez-vous de fermer la balise div ouverte pour chaque texte
            $content = str_replace('[/et_pb_text]', '</div>', $content);


            $content = preg_replace_callback('/\[et_pb_image(.*?)\](\[\/et_pb_image\])?/', function($matches) {
                $attributes = $matches[1];
                $imageSrc = '';
                $altText = '';
                $titleText = '';
                $url = '';

                if (preg_match('/src="([^"]+)"/', $attributes, $srcMatches)) {
                    $imageSrc = $srcMatches[1];
                }
                if (preg_match('/alt="([^"]+)"/', $attributes, $altMatches)) {
                    $altText = $altMatches[1];
                }
                if (preg_match('/title_text="([^"]+)"/', $attributes, $titleMatches)) {
                    $titleText = $titleMatches[1];
                }
                if (preg_match('/url="([^"]+)"/', $attributes, $urlMatches)) {
                    $url = $urlMatches[1];
                }

                // Construire le HTML pour l'image, éventuellement avec un lien
                $html = '';
                if (!empty($url)) {
                    $html .= '<a href="' . $url . '">';
                }
                $html .= '<figure class="d-flex justify-content-center my-5"><img src="' . $imageSrc . '" alt="' . $altText . '" title="' . $titleText . '" class="img-fluid"></figure>';
                if (!empty($url)) {
                    $html .= '</a>';
                }

                return $html;
            }, $content);


        
            return $content;
        
    }
    
    function filterContent($content) {
        
        // Supprimer la chaîne spécifique @ET-DC@...@
        $content = preg_replace('/@ET-DC@[a-zA-Z0-9+\/=]+@/', '', $content);
        // Supprimer tous les shortcodes
        $content = preg_replace('/\[\/?.*?\]/', '', $content);
    
        // Conserver uniquement les titres, paragraphes, images et vidéos
        $content = strip_tags($content, '<h1><h2><h3><h4><h5><h6><p><img><iframe><section><video><figure>');
    
        // Supprimer les balises div et span (et d'autres balises si nécessaire)
        $content = preg_replace('/<\/?div[^>]*>/', '', $content);
        $content = preg_replace('/<\/?span[^>]*>/', '', $content);
        // Ajoutez des lignes similaires ici pour d'autres balises que vous voulez supprimer

        // Supprimer les sauts de ligne
        $content = str_replace("\n", '', $content);
    
        return $content;
    }

    public function isoToEmoji(string $code)
    {
        return implode(
            '',
            array_map(
                fn ($letter) => mb_chr(ord($letter) % 32 + 0x1F1E5),
                str_split($code)
            )
        );
    }

    public function showCountry($countryCode)
    {
        if(null !== $countryCode){
            return \Symfony\Component\Intl\Countries::getName($countryCode);
        }
        return null;
    }

    public function getExperienceText(string $value): string
    {
        $choices = [
            'SM' => '1 an',
            'MD' => '1-3 ans',
            'LG' => '3-5 ans',
            'XL' => '+ de 5 ans', // J'ai modifié la clé ici de 'LG' à 'XL' car 'LG' était dupliqué
        ];

        return $choices[$value] ?? 'N/A';
    }
    
    public function dateDifference(?DateTime $date1, DateTime $date2): string
    {
        $dateActuelle = new DateTime(); // Obtenez la date actuelle
    
        // Si la date de fin est nulle, cela signifie que l'expérience est en cours
        if ($date1 === null) {
            $interval = $date2->diff($dateActuelle);
        } else {
            $interval = $date1->diff($date2);
        }

        $result = '';

        if ($interval->y > 0) {
            $result .= $interval->y . ' années ';
        }
        if ($interval->m > 0) {
            $result .= $interval->m . ' mois ';
        }

        return trim($result);
    }

    public function yearsDifference(DateTime $date1, DateTime $date2): string
    {
        $interval = $date1->diff($date2);

        $result = '';

        if ($interval->y > 0) {
            $result .= $interval->y . ' années ';
        }

        return trim($result);
    }
    
    public function countTokens($text) {
        // Sépare le texte en mots en utilisant des espaces et d'autres séparateurs courants
        $words = preg_split('/[\s,\.;:\?!]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    
        // Compter les mots
        $wordCount = count($words);
    
        // Compter tous les caractères non alphabétiques et non numériques
        $specialCharactersCount = 0;
        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            if (!ctype_alnum($text[$i])) {
                $specialCharactersCount++;
            }
        }
    
        // Total estimé des tokens
        $totalTokens = $wordCount + $specialCharactersCount;
    
        return $totalTokens;
    }
    
    function formatDuration(string $duration): string {
        $interval = new DateInterval($duration);
    
        $hours = $interval->h; // Heures
        $minutes = $interval->i; // Minutes
        $seconds = $interval->s; // Secondes
    
        $formattedDuration = "";
        if ($hours > 0) {
            $formattedDuration .= "{$hours} heures ";
        }
        if ($minutes > 0) {
            $formattedDuration .= "{$minutes} minutes ";
        }
        if ($seconds > 0 || $formattedDuration === "") {
            $formattedDuration .= "{$seconds} secondes";
        }
    
        return trim($formattedDuration);
    }

    public function formatTimeDiff(\DateTime $publishedAt) {
        $now = new \DateTime();
        $interval = $publishedAt->diff($now);

        if ($interval->y > 0) {
            return "Il y a " . $interval->y . " années";
        } elseif ($interval->m > 0) {
            return "Il y a " . $interval->m . " mois";
        } elseif ($interval->d > 0) {
            return "Il y a " . $interval->d . " jours";
        } else {
            return "aujourd'hui";
        }
    }

    public function getEditedCv(string $cvName)
    {
        $safeFileName = "";
        $safeFileName = $this->em->getRepository(EditedCv::class)->findBy(
            ['cvLink' => $cvName], 
            ['id' => 'DESC'] 
        );
        if (!empty($safeFileName)) { 
            if (is_array($safeFileName)) { 
                if (isset($safeFileName[0]) && $safeFileName[0] instanceof EditedCv) {
                    return $safeFileName[0];
                }
            } else {
                return $safeFileName;
            }
        }
        return null;

    }
    
    public function timeAgo($datetime)
    {
        if (!$datetime instanceof \DateTimeInterface) {
            return 'Jamais connecté';
        }

        $now = new \DateTime();
        $diff = $now->diff($datetime);

        // Calculer les semaines à partir des jours
        $weeks = floor($diff->d / 7);
        $days = $diff->d % 7;

        $string = [
            'y' => ['année', 'années'],
            'm' => ['mois', 'mois'],
            'w' => ['semaine', 'semaines'],
            'd' => ['jour', 'jours'],
            'h' => ['heure', 'heures'],
            'i' => ['minute', 'minutes'],
            's' => ['seconde', 'secondes'],
        ];

        foreach ($string as $key => $value) {
            $number = $key === 'w' ? $weeks : $diff->$key;
            if ($number) {
                return 'il y a ' . $number . ' ' . ($number > 1 ? $value[1] : $value[0]);
            }
        }

        return 'à l\'instant';
    }

    public function checkAvailability(User $user): string
    {
        $status = '<i class="bi bi-exclamation-circle-fill"></i> Non renseigné';
        if($user->getCandidateProfile() instanceof CandidateProfile){
            $availability = $user->getCandidateProfile()->getAvailability();
            if($availability instanceof Availability){
                switch ($availability->getNom()) {
                    case 'immediate':
                        $status = '<i class="bi bi-circle-fill text-danger"></i> Disponible';
                        break;

                    case 'from-date':
                        $status = '<i class="bi bi-circle-fill text-warning"></i> A partir du '. $availability->getDateFin()->format('d/m/Y');
                        break;

                    case 'full-time':
                        $status = '<i class="bi bi-circle-fill text-danger"></i> Temps plein';
                        break;

                    case 'part-time':
                        $status = '<i class="bi bi-circle-fill text-warning"></i> Temps partiel';
                        break;

                    case 'not-available':
                        $status = '<i class="bi bi-circle text-secondary"></i> Non disponible';
                        break;
                    
                    default:
                        $status = '<i class="bi bi-exclamation-circle-fill"></i> Non renseigné';
                        break;
                }
            }
        }

        return $status;
    }

    public function getAge(CandidateProfile $candidat):string
    {
        // Calcul de l'âge
        $now = new \DateTime();
        $age = "Non renseigné";
        if ($candidat->getBirthday() !== null) {
            $age = $now->diff($candidat->getBirthday())->y;
        }

        return $age;
    }

    function getPseudo(CandidateProfile $candidat):string
    {
        // Sépare les prénoms par des espaces et stocke-les dans un tableau.
        $prenoms = explode(' ', $candidat->getCandidat()->getPrenom());
    
        // Initialise le pseudo avec le premier prénom.
        $pseudo = $prenoms[0];
    
        // Ajoute les initiales des prénoms suivants suivies d'un point.
        for ($i = 1; $i < count($prenoms); $i++) {
            $pseudo .= substr($prenoms[$i], 0, 1) . '.';
        }
    
        // Ajoute l'initiale du nom de famille suivie d'un point.
        $pseudo .= ' '.substr($candidat->getCandidat()->getNom(), 0, 1) . '.';
    
        return $pseudo;
    }


    function invitation(?string $status):string
    {
        $badge = '';
        switch ($status) {
            case 'USED':
                $badge = '<span class="badge bg-dark h2">Utilisé</span>';
                break;
            
            default:
                $badge = '<span class="badge bg-danger h2"><i class="bi bi-hourglass-split"></i> En attente</span>';
                break;
        }
    
        return $badge;
    }

    public function generatePseudo(CandidateProfile $candidat)
    {
        $letters = 'OT';
        $paddedId = sprintf('%04d', $candidat->getId());

        return $letters . $paddedId;
    }

    public function generateReference(EntrepriseProfile $entreprise)
    {
        $letters = 'OT-REC';
        $paddedId = sprintf('%04d', $entreprise->getId());

        return $letters . $paddedId;
    }

    public function getTarifCandidat(CandidateProfile $candidat)
    {
        $tarif = '<i class="bi bi-exclamation-circle-fill"></i> Non renseigné';
        $tarifCandidat = $candidat->getTarifCandidat();
        if($tarifCandidat instanceof TarifCandidat){
            switch ($tarifCandidat->getTypeTarif()) {
                case TarifCandidat::TYPE_HOURLY :
                    $tarif = '<strong>'.$tarifCandidat->getMontant().' '.$this->getCurrency($tarifCandidat).'</strong> par heure';
                    break;
                
                case TarifCandidat::TYPE_DAILY :
                    $tarif = '<strong>'.$tarifCandidat->getMontant().' '.$this->getCurrency($tarifCandidat).'</strong> par jour';
                    break;

                case TarifCandidat::TYPE_MONTHLY :
                    $tarif = '<strong>'.$tarifCandidat->getMontant().' '.$this->getCurrency($tarifCandidat).'</strong> par mois';
                    break;
            }
        }

        return $tarif;
    }

    public function getCurrency(TarifCandidat $tarifCandidat)
    {
        $currency = '<i class="bi bi-ban px-4"></i>';
        if($tarifCandidat->getCurrency() instanceof Devise){
            $currency = $tarifCandidat->getCurrency()->getSymbole();
        }else{
            $currency = TarifCandidat::getDeviseSymbol($tarifCandidat->getDevise());
        }

        return $currency;
    }

    public function getTarifForfait(Assignation $assignation)
    {
        $tarif = '<i class="bi bi-ban px-4"></i>';
        $forfait = $assignation->getForfaitAssignation();
        if($forfait instanceof Forfait){
            switch ($forfait->getAssignation()->getForfaitAssignation()->getTypeForfait()) {
                case Forfait::TYPE_HOURLY :
                    $tarif = '<strong>'.$forfait->getMontant().' '.$forfait->getDevise().'</strong> par heure';
                    break;
                
                case Forfait::TYPE_DAILY :
                    $tarif = '<strong>'.$forfait->getMontant().' '.$forfait->getDevise().'</strong> par jour';
                    break;

                case Forfait::TYPE_MONTHLY :
                    $tarif = '<strong>'.$forfait->getMontant().' '.$forfait->getDevise().'</strong> par mois';
                    break;
            }
        }

        return $tarif;
    }

    public function getTypeAssignation(Assignation $assignation)
    {
        $type = '<strong>OLONA</strong>';
        switch ($assignation->getRolePositionVisee()) {
            case Assignation::TYPE_CANDIDAT :
                $type = '<strong>Candidature spontannée</strong><br><span class="text-muted small">déposée le '.$assignation->getDateAssignation()->format('d/m/Y').'</span>';
                break;
            
            case Assignation::TYPE_OLONA :
                $type = '<strong>OLONA</strong><br><span class="text-muted small">suggéré le '.$assignation->getDateAssignation()->format('d/m/Y').'</span>';
                break;
        }

        return $type;
    }

    public function getForfaitAssignation(Assignation $assignation)
    {
        $forfait = '<strong><i class="bi bi-ban"></i></strong>';
        if ($assignation->getForfaitAssignation() instanceof Forfait) {
            $forfait = '<strong>'.$assignation->getForfaitAssignation()->getMontant().' '.$assignation->getForfaitAssignation()->getDevise().'</strong> '.Forfait::arrayInverseTarifType()[$assignation->getForfaitAssignation()->getTypeForfait()].'';
        }

        return $forfait;
    }

    public function getStatusAssignation(Assignation $assignation)
    {
        switch ($assignation->getStatus()) {
            case Assignation::STATUS_ACCEPTED :
                $status = '<span class="badge bg-success">Acceptée</span>';
                break;
            
            case Assignation::STATUS_REFUSED :
                $status = '<span class="badge bg-danger">Refusée</span>';
                break;
            
            case Assignation::STATUS_MODERATED :
                $status = '<span class="badge bg-primary">Moderée</span>';
                break;
            
            case Assignation::STATUS_PENDING :
                $status = '<span class="badge bg-dark">En attente</span>';
                break;
            
            default:
                $status = '<span class="badge bg-dark">En attente</span>';
                break;
        }

        return $status;
    }

    public function getAssignByEntreprise(EntrepriseProfile $entreprise)
    {
        return $this->assignationRepository->findAssignByEntreprise($entreprise);
    }

    public function findValidJobListing(EntrepriseProfile $entreprise)
    {
        return $this->jobListingRepository->findByEntrepriseAndStatus($entreprise, 'PUBLISHED');
    }

    public function findPendingJobListing(EntrepriseProfile $entreprise)
    {
        return $this->jobListingRepository->findByEntrepriseAndStatus($entreprise, 'PENDING');
    }

    public function countUnReadNotification(User $user)
    {
        return count($this->notificationRepository->findByDestinataireAndStatusNot(
            $user, 
            ['id' => 'DESC'], 
            Notification::STATUS_DELETED,
            0
        ));
    }
}