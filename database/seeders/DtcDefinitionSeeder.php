<?php

namespace Database\Seeders;

use App\Models\DtcCause;
use App\Models\DtcDefinition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Knowledge base of the most common generic P0 trouble codes, in French.
 *
 * Idempotent: definitions are upserted by (code, make = '', engine = '') and
 * their causes are replaced, so it can run on fresh installs and updates.
 */
class DtcDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = $this->definitions();
        $now = now();

        // Bulk statements only: a remote database (Neon) costs a network round
        // trip per query, so row-by-row seeding would take minutes.
        DB::transaction(function () use ($definitions, $now): void {
            DtcDefinition::query()->upsert(
                collect($definitions)->map(fn (array $entry, string $code): array => [
                    'code' => $code,
                    'make' => '',
                    'engine' => '',
                    'title' => $entry['title'],
                    'system' => $entry['system'],
                    'explanation' => $entry['explanation'],
                    'urgency' => $entry['urgency'],
                    'can_drive_note' => $entry['note'],
                    'source' => 'kb',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
                ['code', 'make', 'engine'],
                ['title', 'system', 'explanation', 'urgency', 'can_drive_note', 'source', 'updated_at'],
            );

            $ids = DtcDefinition::query()
                ->where('make', '')
                ->where('engine', '')
                ->whereIn('code', array_keys($definitions))
                ->pluck('id', 'code');

            DtcCause::query()->whereIn('dtc_definition_id', $ids->values())->delete();

            $causes = [];
            foreach ($definitions as $code => $entry) {
                foreach ($entry['causes'] as $position => [$label, $probability, $part, $difficulty, $costMin, $costMax]) {
                    $causes[] = [
                        'dtc_definition_id' => $ids[$code],
                        'label' => $label,
                        'probability' => $probability,
                        'part' => $part,
                        'difficulty' => $difficulty,
                        'cost_min' => $costMin,
                        'cost_max' => $costMax,
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            foreach (array_chunk($causes, 200) as $chunk) {
                DtcCause::query()->insert($chunk);
            }
        });
    }

    /**
     * @return array<string, array{title: string, system: string, explanation: string, urgency: string, note: string, causes: list<array{0: string, 1: float, 2: string, 3: string, 4: int, 5: int}>}>
     */
    private function definitions(): array
    {
        $definitions = [
            'P0010' => $this->entry('Circuit de l\'actionneur de calage d\'arbre à cames (admission, banc 1)', 'Distribution variable', 'Le calculateur n\'arrive pas à piloter le déphaseur d\'arbre à cames. C\'est comme une porte dont la poignée ne répond plus : le moteur tourne, mais sans optimiser l\'ouverture des soupapes.', 'caution', 'Tu peux rouler doucement, mais attends-toi à moins de puissance et un ralenti instable.', [
                ['Électrovanne de déphaseur défectueuse', 0.5, 'Électrovanne VVT', 'facile', 80, 250],
                ['Connecteur ou faisceau endommagé', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Huile sale ou niveau bas', 0.2, 'Vidange', 'facile', 60, 150],
            ]),
            'P0011' => $this->entry('Calage d\'arbre à cames trop avancé (admission, banc 1)', 'Distribution variable', 'L\'arbre à cames d\'admission n\'est pas à la position demandée. Le moteur « respire » à contretemps, ce qui coûte de la puissance et peut le faire brouter.', 'caution', 'Évite les tirs : vérifie d\'abord le niveau et la qualité de l\'huile.', [
                ['Huile trop vieille ou mauvaise viscosité', 0.4, 'Vidange', 'facile', 60, 150],
                ['Électrovanne de déphaseur encrassée', 0.35, 'Électrovanne VVT', 'facile', 80, 250],
                ['Chaîne de distribution détendue', 0.25, 'Kit chaîne de distribution', 'difficile', 600, 1800],
            ]),
            'P0016' => $this->entry('Corrélation vilebrequin / arbre à cames (banc 1, capteur A)', 'Distribution', 'Le calculateur compare la position du vilebrequin et de l\'arbre à cames, et elles ne collent plus. Souvent, la chaîne ou la courroie a pris du jeu.', 'stop', 'Ne force pas : une distribution qui saute peut casser le moteur.', [
                ['Chaîne de distribution allongée', 0.45, 'Kit chaîne de distribution', 'difficile', 600, 1800],
                ['Capteur d\'arbre à cames défectueux', 0.3, 'Capteur AAC', 'facile', 60, 200],
                ['Déphaseur grippé', 0.25, 'Déphaseur d\'arbre à cames', 'difficile', 300, 900],
            ]),
            'P0030' => $this->entry('Circuit de chauffage de la sonde lambda amont (banc 1, sonde 1)', 'Antipollution', 'La sonde lambda a besoin d\'être chaude pour mesurer l\'oxygène. Sa résistance de chauffe ne répond plus, donc la mesure est lente à froid.', 'ok', 'Tu peux rouler. La consommation peut grimper un peu à froid.', [
                ['Sonde lambda amont HS', 0.7, 'Sonde lambda amont', 'facile', 80, 250],
                ['Fusible ou relais de chauffe', 0.2, 'Fusible', 'facile', 5, 50],
                ['Faisceau abîmé près de l\'échappement', 0.1, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0087' => $this->entry('Pression de rampe d\'injection trop basse', 'Alimentation carburant', 'Le carburant n\'arrive pas avec assez de pression aux injecteurs. Le moteur manque d\'essence ou de gazole à pleine charge, comme un coureur à bout de souffle.', 'caution', 'Évite les tirs et la pleine charge : le mélange peut devenir pauvre.', [
                ['Filtre à carburant colmaté', 0.35, 'Filtre à carburant', 'facile', 40, 150],
                ['Pompe haute pression fatiguée', 0.35, 'Pompe HP', 'difficile', 400, 1500],
                ['Capteur de pression de rampe défaillant', 0.3, 'Capteur de pression rampe', 'moyenne', 100, 300],
            ]),
            'P0088' => $this->entry('Pression de rampe d\'injection trop haute', 'Alimentation carburant', 'Il y a trop de pression dans la rampe. Le régulateur ne relâche plus assez, ce qui peut faire forcer les injecteurs.', 'caution', 'Roule doucement et fais vérifier rapidement.', [
                ['Régulateur de pression bloqué', 0.5, 'Régulateur de pression', 'moyenne', 150, 400],
                ['Capteur de pression de rampe faussé', 0.35, 'Capteur de pression rampe', 'moyenne', 100, 300],
                ['Retour carburant obstrué', 0.15, 'Durite de retour', 'facile', 30, 120],
            ]),
            'P0100' => $this->entry('Circuit du débitmètre d\'air', 'Admission', 'Le débitmètre mesure l\'air qui entre dans le moteur. Son signal est incohérent, donc le calculateur dose le carburant à l\'aveugle.', 'caution', 'Tu peux rouler, mais avec moins de punch.', [
                ['Débitmètre encrassé ou HS', 0.6, 'Débitmètre d\'air', 'facile', 80, 300],
                ['Connecteur oxydé', 0.25, 'Connecteur', 'facile', 10, 80],
                ['Prise d\'air après le débitmètre', 0.15, 'Durite d\'admission', 'facile', 30, 150],
            ]),
            'P0101' => $this->entry('Débitmètre d\'air : plage / performance', 'Admission', 'Le débit d\'air mesuré ne correspond pas à ce que le calculateur attend pour ce régime. Souvent un débitmètre sale ou une fuite d\'air.', 'caution', 'Tu peux rouler, évite les tirs tant que ce n\'est pas réglé.', [
                ['Débitmètre encrassé', 0.45, 'Débitmètre d\'air', 'facile', 15, 300],
                ['Fuite d\'air à l\'admission', 0.35, 'Durite d\'admission', 'facile', 30, 150],
                ['Filtre à air colmaté', 0.2, 'Filtre à air', 'facile', 15, 60],
            ]),
            'P0102' => $this->entry('Débitmètre d\'air : signal trop faible', 'Admission', 'Le débitmètre envoie un signal trop bas, comme s\'il n\'entrait presque pas d\'air.', 'caution', 'Roule tranquille, le moteur peut passer en mode dégradé.', [
                ['Débitmètre HS', 0.5, 'Débitmètre d\'air', 'facile', 80, 300],
                ['Fil coupé ou connecteur débranché', 0.35, 'Faisceau électrique', 'facile', 10, 120],
                ['Fuite d\'air avant le débitmètre', 0.15, 'Durite d\'admission', 'facile', 30, 150],
            ]),
            'P0103' => $this->entry('Débitmètre d\'air : signal trop élevé', 'Admission', 'Le débitmètre annonce plus d\'air que possible. Le signal est probablement parasité ou en court-circuit.', 'caution', 'Roule tranquille, le moteur peut passer en mode dégradé.', [
                ['Débitmètre HS', 0.55, 'Débitmètre d\'air', 'facile', 80, 300],
                ['Court-circuit sur le faisceau', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Masse moteur défaillante', 0.15, 'Tresse de masse', 'facile', 20, 80],
            ]),
            'P0106' => $this->entry('Capteur de pression d\'admission (MAP) : plage / performance', 'Admission', 'Le capteur qui mesure la pression dans le collecteur donne une valeur peu crédible. Sur un turbo, ça touche directement la gestion de la pression de suralimentation.', 'caution', 'Évite les tirs : la suralimentation est mal pilotée.', [
                ['Capteur MAP encrassé', 0.45, 'Capteur MAP', 'facile', 50, 180],
                ['Fuite d\'air ou durite fendue', 0.35, 'Durite de suralimentation', 'facile', 30, 200],
                ['Connecteur oxydé', 0.2, 'Connecteur', 'facile', 10, 80],
            ]),
            'P0107' => $this->entry('Capteur de pression d\'admission (MAP) : signal trop faible', 'Admission', 'Le capteur MAP envoie un signal quasi nul. Le calculateur ne sait plus combien d\'air remplit les cylindres.', 'caution', 'Roule doucement, le moteur peut manquer de puissance.', [
                ['Capteur MAP HS', 0.5, 'Capteur MAP', 'facile', 50, 180],
                ['Fil d\'alimentation coupé', 0.35, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Connecteur débranché', 0.15, 'Connecteur', 'facile', 0, 50],
            ]),
            'P0108' => $this->entry('Capteur de pression d\'admission (MAP) : signal trop élevé', 'Admission', 'Le capteur MAP annonce une pression trop haute pour être vraie.', 'caution', 'Roule doucement, le moteur peut manquer de puissance.', [
                ['Capteur MAP HS', 0.5, 'Capteur MAP', 'facile', 50, 180],
                ['Court-circuit au plus', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Prise de pression bouchée', 0.2, 'Durite de prise de pression', 'facile', 10, 60],
            ]),
            'P0110' => $this->entry('Circuit du capteur de température d\'air d\'admission', 'Admission', 'Le capteur de température d\'air (IAT) ne répond plus correctement. Or c\'est lui qui dit au moteur si l\'air est chaud, et donc s\'il doit réduire l\'avance.', 'caution', 'Évite les tirs : sans IAT fiable, la protection anti-cliquetis est approximative.', [
                ['Capteur IAT HS', 0.55, 'Capteur IAT', 'facile', 20, 100],
                ['Connecteur oxydé', 0.3, 'Connecteur', 'facile', 10, 80],
                ['Fil coupé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0113' => $this->entry('Capteur de température d\'air d\'admission : signal trop élevé', 'Admission', 'Le capteur IAT lit un air anormalement froid ou un circuit ouvert. Le moteur croit respirer de l\'air glacé.', 'caution', 'Évite les tirs, le calcul d\'avance est faussé.', [
                ['Capteur IAT débranché ou HS', 0.6, 'Capteur IAT', 'facile', 20, 100],
                ['Fil coupé', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Débitmètre intégrant l\'IAT défaillant', 0.1, 'Débitmètre d\'air', 'facile', 80, 300],
            ]),
            'P0115' => $this->entry('Circuit du capteur de température moteur', 'Refroidissement', 'Le capteur de température d\'eau ne répond plus. Le calculateur ne sait plus si le moteur est froid ou brûlant.', 'caution', 'Surveille la jauge de température et ne tire pas dans les tours.', [
                ['Sonde de température HS', 0.6, 'Sonde de température d\'eau', 'facile', 30, 120],
                ['Connecteur oxydé', 0.25, 'Connecteur', 'facile', 10, 80],
                ['Fil coupé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0116' => $this->entry('Capteur de température moteur : plage / performance', 'Refroidissement', 'La température d\'eau évolue de façon illogique, par exemple elle ne monte pas assez vite après le démarrage.', 'caution', 'Surveille la température et le niveau de liquide.', [
                ['Thermostat bloqué ouvert', 0.45, 'Thermostat', 'moyenne', 80, 300],
                ['Sonde de température dérivée', 0.4, 'Sonde de température d\'eau', 'facile', 30, 120],
                ['Niveau de liquide bas', 0.15, 'Liquide de refroidissement', 'facile', 15, 60],
            ]),
            'P0117' => $this->entry('Capteur de température moteur : signal trop faible', 'Refroidissement', 'Le capteur annonce un moteur bouillant, souvent à cause d\'un court-circuit. Le ventilateur peut tourner en permanence.', 'caution', 'Vérifie la vraie température avant de rouler fort.', [
                ['Sonde en court-circuit', 0.6, 'Sonde de température d\'eau', 'facile', 30, 120],
                ['Faisceau écrasé', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Surchauffe réelle', 0.1, 'Circuit de refroidissement', 'moyenne', 100, 600],
            ]),
            'P0118' => $this->entry('Capteur de température moteur : signal trop élevé', 'Refroidissement', 'Le capteur annonce un moteur glacé ou un circuit ouvert. Le moteur reste enrichi comme à froid.', 'caution', 'Tu peux rouler doucement, mais la consommation va grimper.', [
                ['Sonde débranchée ou HS', 0.6, 'Sonde de température d\'eau', 'facile', 30, 120],
                ['Fil coupé', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Connecteur oxydé', 0.1, 'Connecteur', 'facile', 10, 80],
            ]),
            'P0120' => $this->entry('Circuit du capteur de position papillon / pédale (A)', 'Gestion moteur', 'Le capteur qui dit où est le papillon (ou la pédale) envoie un signal incohérent. Par sécurité, le calculateur limite souvent la puissance.', 'caution', 'Le mode dégradé peut limiter l\'accélération : reste prudent en insertion.', [
                ['Boîtier papillon encrassé ou HS', 0.5, 'Boîtier papillon', 'moyenne', 150, 500],
                ['Capteur de pédale défectueux', 0.3, 'Pédale d\'accélérateur', 'facile', 80, 250],
                ['Connecteur oxydé', 0.2, 'Connecteur', 'facile', 10, 80],
            ]),
            'P0121' => $this->entry('Capteur de position papillon (A) : plage / performance', 'Gestion moteur', 'La position du papillon ne colle pas avec le débit d\'air mesuré.', 'caution', 'Évite les tirs, la réponse à l\'accélérateur peut être aléatoire.', [
                ['Boîtier papillon encrassé', 0.5, 'Boîtier papillon', 'facile', 20, 500],
                ['Capteur de position usé', 0.35, 'Capteur de position papillon', 'moyenne', 80, 250],
                ['Fuite d\'air', 0.15, 'Durite d\'admission', 'facile', 30, 150],
            ]),
            'P0128' => $this->entry('Température moteur trop basse (thermostat)', 'Refroidissement', 'Le moteur met trop de temps à chauffer. Le plus souvent le thermostat reste ouvert, comme une fenêtre qu\'on n\'arrive plus à fermer en hiver.', 'ok', 'Tu peux rouler. Consommation et usure augmentent à froid.', [
                ['Thermostat bloqué ouvert', 0.7, 'Thermostat', 'moyenne', 80, 300],
                ['Sonde de température dérivée', 0.2, 'Sonde de température d\'eau', 'facile', 30, 120],
                ['Ventilateur qui tourne en permanence', 0.1, 'Relais de ventilateur', 'facile', 20, 100],
            ]),
            'P0130' => $this->entry('Circuit de la sonde lambda amont (banc 1, sonde 1)', 'Antipollution', 'La sonde lambda avant catalyseur ne répond plus correctement. Le mélange air / carburant est réglé moins finement.', 'ok', 'Tu peux rouler, fais-la contrôler sans tarder.', [
                ['Sonde lambda amont HS', 0.6, 'Sonde lambda amont', 'facile', 80, 250],
                ['Fuite à l\'échappement avant la sonde', 0.25, 'Joint d\'échappement', 'moyenne', 50, 250],
                ['Faisceau abîmé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0131' => $this->entry('Sonde lambda amont : tension trop basse (banc 1, sonde 1)', 'Antipollution', 'La sonde lit un mélange très pauvre en permanence. Ça peut venir de la sonde ou d\'une vraie entrée d\'air.', 'caution', 'Évite la pleine charge si le mélange est vraiment pauvre.', [
                ['Sonde lambda amont HS', 0.5, 'Sonde lambda amont', 'facile', 80, 250],
                ['Prise d\'air à l\'admission', 0.3, 'Durite d\'admission', 'facile', 30, 150],
                ['Fuite à l\'échappement', 0.2, 'Joint d\'échappement', 'moyenne', 50, 250],
            ]),
            'P0133' => $this->entry('Sonde lambda amont : réponse lente (banc 1, sonde 1)', 'Antipollution', 'La sonde réagit trop lentement aux variations de mélange : elle vieillit, comme des yeux qui mettent du temps à s\'habituer à la lumière.', 'ok', 'Tu peux rouler. Prévois le remplacement de la sonde.', [
                ['Sonde lambda usée', 0.75, 'Sonde lambda amont', 'facile', 80, 250],
                ['Fuite à l\'échappement', 0.15, 'Joint d\'échappement', 'moyenne', 50, 250],
                ['Huile ou liquide brûlé qui pollue la sonde', 0.1, 'Diagnostic moteur', 'moyenne', 50, 150],
            ]),
            'P0134' => $this->entry('Sonde lambda amont : aucune activité (banc 1, sonde 1)', 'Antipollution', 'La sonde ne donne plus aucun signal exploitable.', 'ok', 'Tu peux rouler, mais le contrôle technique ne passera pas.', [
                ['Sonde lambda HS', 0.6, 'Sonde lambda amont', 'facile', 80, 250],
                ['Chauffage de sonde HS', 0.25, 'Sonde lambda amont', 'facile', 80, 250],
                ['Fil coupé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0135' => $this->entry('Chauffage de la sonde lambda amont (banc 1, sonde 1)', 'Antipollution', 'La résistance qui chauffe la sonde lambda ne fonctionne plus. À froid, la régulation du mélange est retardée.', 'ok', 'Tu peux rouler.', [
                ['Sonde lambda amont HS', 0.7, 'Sonde lambda amont', 'facile', 80, 250],
                ['Fusible de chauffe grillé', 0.2, 'Fusible', 'facile', 5, 50],
                ['Faisceau abîmé', 0.1, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0136' => $this->entry('Circuit de la sonde lambda aval (banc 1, sonde 2)', 'Antipollution', 'La sonde après le catalyseur, qui surveille son efficacité, ne répond plus correctement.', 'ok', 'Tu peux rouler.', [
                ['Sonde lambda aval HS', 0.65, 'Sonde lambda aval', 'facile', 70, 220],
                ['Faisceau abîmé', 0.2, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Fuite à l\'échappement', 0.15, 'Joint d\'échappement', 'moyenne', 50, 250],
            ]),
            'P0137' => $this->entry('Sonde lambda aval : tension trop basse (banc 1, sonde 2)', 'Antipollution', 'La sonde aval lit un mélange pauvre en permanence.', 'ok', 'Tu peux rouler.', [
                ['Sonde lambda aval HS', 0.6, 'Sonde lambda aval', 'facile', 70, 220],
                ['Fuite à l\'échappement', 0.3, 'Joint d\'échappement', 'moyenne', 50, 250],
                ['Faisceau abîmé', 0.1, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0138' => $this->entry('Sonde lambda aval : tension trop haute (banc 1, sonde 2)', 'Antipollution', 'La sonde aval lit un mélange riche en permanence, ou son circuit est en court-circuit.', 'ok', 'Tu peux rouler.', [
                ['Sonde lambda aval HS', 0.6, 'Sonde lambda aval', 'facile', 70, 220],
                ['Mélange réellement trop riche', 0.25, 'Diagnostic injection', 'moyenne', 50, 150],
                ['Court-circuit faisceau', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0141' => $this->entry('Chauffage de la sonde lambda aval (banc 1, sonde 2)', 'Antipollution', 'La chauffe de la sonde après catalyseur ne fonctionne plus.', 'ok', 'Tu peux rouler.', [
                ['Sonde lambda aval HS', 0.7, 'Sonde lambda aval', 'facile', 70, 220],
                ['Fusible de chauffe grillé', 0.2, 'Fusible', 'facile', 5, 50],
                ['Faisceau abîmé', 0.1, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0171' => $this->entry('Mélange trop pauvre (banc 1)', 'Dosage air / carburant', 'Le moteur reçoit trop d\'air par rapport au carburant, et le calculateur est au bout de ses corrections. Comme un feu qui manque de bois : il chauffe et s\'essouffle.', 'caution', 'Évite les tirs : un mélange pauvre à pleine charge peut abîmer les pistons.', [
                ['Prise d\'air (durite, joint d\'admission, recyclage des vapeurs d\'huile)', 0.45, 'Durite / joint d\'admission', 'facile', 30, 250],
                ['Débitmètre encrassé', 0.25, 'Débitmètre d\'air', 'facile', 15, 300],
                ['Pression d\'essence faible', 0.2, 'Pompe / filtre à carburant', 'moyenne', 80, 600],
                ['Injecteur encrassé', 0.1, 'Injecteur', 'moyenne', 100, 400],
            ]),
            'P0172' => $this->entry('Mélange trop riche (banc 1)', 'Dosage air / carburant', 'Il y a trop de carburant par rapport à l\'air. Le moteur consomme plus, encrasse ses bougies et peut fumer noir.', 'caution', 'Tu peux rouler doucement, mais le catalyseur n\'aime pas ça.', [
                ['Injecteur qui fuit', 0.35, 'Injecteur', 'moyenne', 100, 400],
                ['Débitmètre faussé', 0.3, 'Débitmètre d\'air', 'facile', 80, 300],
                ['Pression d\'essence trop haute', 0.2, 'Régulateur de pression', 'moyenne', 100, 300],
                ['Filtre à air colmaté', 0.15, 'Filtre à air', 'facile', 15, 60],
            ]),
            'P0174' => $this->entry('Mélange trop pauvre (banc 2)', 'Dosage air / carburant', 'Même problème que P0171, sur la deuxième rangée de cylindres (moteurs en V).', 'caution', 'Évite les tirs : un mélange pauvre à pleine charge peut abîmer les pistons.', [
                ['Prise d\'air', 0.45, 'Durite / joint d\'admission', 'facile', 30, 250],
                ['Débitmètre encrassé', 0.3, 'Débitmètre d\'air', 'facile', 15, 300],
                ['Pression d\'essence faible', 0.25, 'Pompe / filtre à carburant', 'moyenne', 80, 600],
            ]),
            'P0175' => $this->entry('Mélange trop riche (banc 2)', 'Dosage air / carburant', 'Même problème que P0172, sur la deuxième rangée de cylindres.', 'caution', 'Tu peux rouler doucement.', [
                ['Injecteur qui fuit', 0.4, 'Injecteur', 'moyenne', 100, 400],
                ['Débitmètre faussé', 0.35, 'Débitmètre d\'air', 'facile', 80, 300],
                ['Pression d\'essence trop haute', 0.25, 'Régulateur de pression', 'moyenne', 100, 300],
            ]),
            'P0190' => $this->entry('Circuit du capteur de pression de rampe', 'Alimentation carburant', 'Le capteur qui mesure la pression du carburant dans la rampe ne répond plus. Le moteur passe souvent en mode dégradé.', 'caution', 'Roule doucement jusqu\'au garage.', [
                ['Capteur de pression de rampe HS', 0.6, 'Capteur de pression rampe', 'moyenne', 100, 300],
                ['Connecteur oxydé', 0.25, 'Connecteur', 'facile', 10, 80],
                ['Fil coupé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0201' => $this->entry('Circuit de l\'injecteur du cylindre 1', 'Injection', 'Le calculateur n\'arrive plus à commander l\'injecteur du cylindre 1. Ce cylindre ne reçoit plus (ou mal) de carburant.', 'stop', 'Arrête-toi dès que possible : un cylindre qui ne brûle pas peut détruire le catalyseur.', [
                ['Injecteur HS', 0.55, 'Injecteur', 'moyenne', 100, 400],
                ['Connecteur d\'injecteur débranché ou oxydé', 0.3, 'Connecteur', 'facile', 10, 80],
                ['Faisceau coupé', 0.15, 'Faisceau électrique', 'moyenne', 50, 250],
            ]),
            'P0217' => $this->entry('Surchauffe moteur', 'Refroidissement', 'Le moteur dépasse sa température de sécurité. C\'est le genre d\'alerte où chaque minute compte.', 'stop', 'Arrête-toi, coupe le moteur et laisse refroidir. N\'ouvre pas le vase d\'expansion à chaud.', [
                ['Manque de liquide de refroidissement', 0.4, 'Liquide de refroidissement', 'facile', 15, 60],
                ['Ventilateur ou thermostat HS', 0.35, 'Thermostat / motoventilateur', 'moyenne', 80, 450],
                ['Pompe à eau défaillante', 0.25, 'Pompe à eau', 'difficile', 250, 700],
            ]),
            'P0219' => $this->entry('Surrégime moteur', 'Gestion moteur', 'Le moteur a dépassé son régime maximal, par exemple après un mauvais rétrogradage.', 'caution', 'Si c\'est arrivé une fois, surveille bruits et consommation d\'huile.', [
                ['Mauvais rapport engagé', 0.7, 'Aucune pièce', 'facile', 0, 0],
                ['Capteur de régime défaillant', 0.2, 'Capteur vilebrequin', 'facile', 50, 180],
                ['Embrayage qui patine', 0.1, 'Kit embrayage', 'difficile', 600, 1500],
            ]),
            'P0234' => $this->entry('Suralimentation excessive (overboost)', 'Suralimentation', 'Le turbo souffle plus fort que prévu. Le moteur coupe souvent la suralimentation pour se protéger : c\'est un fusible qui saute avant le moteur.', 'caution', 'Ne tire pas tant que ce n\'est pas réglé. Sur une voiture reprogrammée, fais vérifier la carto.', [
                ['Wastegate ou actionneur grippé', 0.4, 'Actionneur de wastegate', 'moyenne', 150, 600],
                ['Électrovanne de régulation de pression HS', 0.35, 'Électrovanne N75', 'facile', 50, 200],
                ['Cartographie trop agressive', 0.25, 'Reprogrammation', 'moyenne', 100, 400],
            ]),
            'P0236' => $this->entry('Capteur de pression de suralimentation : plage / performance', 'Suralimentation', 'La pression de turbo mesurée ne colle pas avec celle attendue.', 'caution', 'Évite les tirs.', [
                ['Capteur de pression encrassé', 0.4, 'Capteur de pression de suralimentation', 'facile', 50, 180],
                ['Fuite de suralimentation', 0.4, 'Durite / échangeur', 'moyenne', 50, 500],
                ['Turbo fatigué', 0.2, 'Turbo', 'difficile', 800, 2500],
            ]),
            'P0243' => $this->entry('Circuit de l\'électrovanne de wastegate', 'Suralimentation', 'Le calculateur ne parvient plus à piloter la soupape de décharge du turbo.', 'caution', 'Évite les tirs : la pression de turbo n\'est plus maîtrisée.', [
                ['Électrovanne HS', 0.55, 'Électrovanne N75', 'facile', 50, 200],
                ['Connecteur ou fil abîmé', 0.3, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Durite de dépression fendue', 0.15, 'Durite de dépression', 'facile', 10, 60],
            ]),
            'P0299' => $this->entry('Suralimentation insuffisante (underboost)', 'Suralimentation', 'Le turbo n\'atteint pas la pression demandée. Souvent une fuite : l\'air comprimé s\'échappe avant d\'arriver au moteur, comme un ballon percé.', 'caution', 'Tu peux rouler doucement. Évite les tirs, le résultat sera faussé.', [
                ['Fuite sur durite ou échangeur', 0.45, 'Durite de suralimentation / échangeur', 'moyenne', 50, 500],
                ['Wastegate qui reste ouverte', 0.25, 'Actionneur de wastegate', 'moyenne', 150, 600],
                ['Turbo usé', 0.2, 'Turbo', 'difficile', 800, 2500],
                ['Électrovanne de régulation HS', 0.1, 'Électrovanne N75', 'facile', 50, 200],
            ]),
            'P0300' => $this->entry('Ratés d\'allumage multiples / aléatoires', 'Allumage', 'Plusieurs cylindres ratent leur combustion. Le moteur tremble et l\'essence non brûlée part dans l\'échappement, ce qui peut faire fondre le catalyseur.', 'stop', 'Si le voyant clignote, arrête-toi. Sinon roule très doucement jusqu\'au garage.', [
                ['Bougies usées', 0.35, 'Bougies d\'allumage', 'facile', 40, 150],
                ['Prise d\'air ou mélange pauvre', 0.25, 'Durite d\'admission', 'facile', 30, 250],
                ['Bobine défaillante', 0.2, 'Bobine d\'allumage', 'facile', 50, 200],
                ['Pression d\'essence faible', 0.2, 'Pompe / filtre à carburant', 'moyenne', 80, 600],
            ]),
            'P0325' => $this->entry('Circuit du capteur de cliquetis (banc 1)', 'Allumage', 'Le capteur qui « écoute » le cliquetis ne répond plus. Par sécurité, le calculateur retarde l\'avance : tu perds de la puissance.', 'caution', 'Évite les tirs : sans capteur de cliquetis, le moteur n\'est plus protégé.', [
                ['Capteur de cliquetis HS', 0.5, 'Capteur de cliquetis', 'moyenne', 80, 300],
                ['Connecteur oxydé', 0.3, 'Connecteur', 'facile', 10, 80],
                ['Faisceau abîmé', 0.2, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0335' => $this->entry('Circuit du capteur de position vilebrequin', 'Gestion moteur', 'Le capteur qui donne le régime et la position du moteur ne répond plus. Sans lui, le moteur peut caler ou refuser de démarrer.', 'stop', 'Le moteur peut couper à tout moment : ne prends pas l\'autoroute.', [
                ['Capteur vilebrequin HS', 0.6, 'Capteur vilebrequin', 'facile', 50, 180],
                ['Cible (couronne) encrassée ou abîmée', 0.2, 'Cible de capteur', 'difficile', 100, 600],
                ['Connecteur ou faisceau abîmé', 0.2, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0340' => $this->entry('Circuit du capteur de position arbre à cames (banc 1)', 'Gestion moteur', 'Le capteur d\'arbre à cames ne répond plus. Le moteur démarre plus difficilement et perd en souplesse.', 'caution', 'Tu peux rouler, mais le redémarrage peut devenir difficile.', [
                ['Capteur AAC HS', 0.6, 'Capteur AAC', 'facile', 60, 200],
                ['Connecteur oxydé', 0.25, 'Connecteur', 'facile', 10, 80],
                ['Distribution décalée', 0.15, 'Kit de distribution', 'difficile', 400, 1500],
            ]),
            'P0341' => $this->entry('Capteur de position arbre à cames : plage / performance', 'Gestion moteur', 'Le signal d\'arbre à cames arrive au mauvais moment par rapport au vilebrequin.', 'caution', 'Fais vérifier la distribution rapidement.', [
                ['Capteur AAC défectueux', 0.45, 'Capteur AAC', 'facile', 60, 200],
                ['Distribution décalée', 0.35, 'Kit de distribution', 'difficile', 400, 1500],
                ['Faisceau parasité', 0.2, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0351' => $this->entry('Circuit primaire de la bobine d\'allumage A', 'Allumage', 'Le calculateur ne parvient plus à commander la bobine d\'un cylindre. Ce cylindre risque de ne plus allumer.', 'caution', 'Roule doucement : un raté prolongé abîme le catalyseur.', [
                ['Bobine HS', 0.6, 'Bobine d\'allumage', 'facile', 50, 200],
                ['Connecteur de bobine oxydé', 0.25, 'Connecteur', 'facile', 10, 80],
                ['Faisceau abîmé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0380' => $this->entry('Circuit des bougies de préchauffage', 'Préchauffage (diesel)', 'Le système de préchauffage du diesel ne fonctionne plus correctement. Les démarrages à froid deviennent difficiles.', 'ok', 'Tu peux rouler. Par temps froid, le démarrage peut être laborieux.', [
                ['Bougie de préchauffage HS', 0.5, 'Bougies de préchauffage', 'moyenne', 80, 400],
                ['Boîtier de préchauffage HS', 0.35, 'Boîtier de préchauffage', 'facile', 80, 250],
                ['Faisceau abîmé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0400' => $this->entry('Débit de recirculation des gaz d\'échappement (EGR)', 'Antipollution', 'La vanne EGR, qui réinjecte une partie des gaz d\'échappement pour réduire la pollution, ne donne pas le bon débit.', 'ok', 'Tu peux rouler, des à-coups sont possibles.', [
                ['Vanne EGR encrassée', 0.6, 'Vanne EGR', 'moyenne', 100, 500],
                ['Conduits EGR bouchés', 0.25, 'Conduits EGR', 'moyenne', 100, 400],
                ['Électrovanne de commande HS', 0.15, 'Électrovanne EGR', 'facile', 50, 200],
            ]),
            'P0401' => $this->entry('Débit EGR insuffisant', 'Antipollution', 'La vanne EGR laisse passer moins de gaz que prévu. Souvent la calamine bouche le passage.', 'ok', 'Tu peux rouler. Un décrassage peut suffire.', [
                ['Vanne EGR encrassée', 0.6, 'Vanne EGR', 'moyenne', 100, 500],
                ['Conduits EGR bouchés', 0.3, 'Conduits EGR', 'moyenne', 100, 400],
                ['Capteur de position EGR', 0.1, 'Vanne EGR', 'moyenne', 100, 500],
            ]),
            'P0402' => $this->entry('Débit EGR excessif', 'Antipollution', 'La vanne EGR reste trop ouverte. Le moteur étouffe au ralenti et peut caler.', 'caution', 'Tu peux rouler doucement, attention aux calages au ralenti.', [
                ['Vanne EGR bloquée ouverte', 0.65, 'Vanne EGR', 'moyenne', 100, 500],
                ['Électrovanne de commande HS', 0.2, 'Électrovanne EGR', 'facile', 50, 200],
                ['Durite de dépression mal branchée', 0.15, 'Durite de dépression', 'facile', 10, 60],
            ]),
            'P0403' => $this->entry('Circuit de commande de la vanne EGR', 'Antipollution', 'Le calculateur n\'arrive plus à commander électriquement la vanne EGR.', 'ok', 'Tu peux rouler.', [
                ['Vanne EGR HS', 0.55, 'Vanne EGR', 'moyenne', 100, 500],
                ['Connecteur oxydé', 0.3, 'Connecteur', 'facile', 10, 80],
                ['Faisceau abîmé', 0.15, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0420' => $this->entry('Efficacité du catalyseur insuffisante (banc 1)', 'Antipollution', 'Le catalyseur ne nettoie plus assez les gaz. Les deux sondes lambda, avant et après, voient presque la même chose : le filtre ne fait plus son travail.', 'ok', 'Tu peux rouler, mais le contrôle technique ne passera pas.', [
                ['Catalyseur usé', 0.55, 'Catalyseur', 'moyenne', 400, 1500],
                ['Sonde lambda aval défaillante', 0.25, 'Sonde lambda aval', 'facile', 70, 220],
                ['Fuite à l\'échappement', 0.2, 'Joint d\'échappement', 'moyenne', 50, 250],
            ]),
            'P0430' => $this->entry('Efficacité du catalyseur insuffisante (banc 2)', 'Antipollution', 'Même problème que P0420, sur la deuxième ligne d\'échappement.', 'ok', 'Tu peux rouler, mais le contrôle technique ne passera pas.', [
                ['Catalyseur usé', 0.55, 'Catalyseur', 'moyenne', 400, 1500],
                ['Sonde lambda aval défaillante', 0.25, 'Sonde lambda aval', 'facile', 70, 220],
                ['Fuite à l\'échappement', 0.2, 'Joint d\'échappement', 'moyenne', 50, 250],
            ]),
            'P0440' => $this->entry('Système de récupération des vapeurs d\'essence (EVAP)', 'Antipollution', 'Le circuit qui récupère les vapeurs d\'essence du réservoir a un défaut.', 'ok', 'Tu peux rouler. Vérifie d\'abord le bouchon de réservoir.', [
                ['Bouchon de réservoir mal serré ou abîmé', 0.4, 'Bouchon de réservoir', 'facile', 15, 50],
                ['Électrovanne de purge canister HS', 0.35, 'Électrovanne canister', 'facile', 50, 180],
                ['Durite EVAP fissurée', 0.25, 'Durite EVAP', 'moyenne', 30, 200],
            ]),
            'P0442' => $this->entry('Petite fuite dans le système EVAP', 'Antipollution', 'Une petite fuite laisse échapper des vapeurs d\'essence.', 'ok', 'Tu peux rouler. Resserre le bouchon de réservoir et efface le code.', [
                ['Bouchon de réservoir', 0.45, 'Bouchon de réservoir', 'facile', 15, 50],
                ['Durite EVAP fissurée', 0.35, 'Durite EVAP', 'moyenne', 30, 200],
                ['Canister fissuré', 0.2, 'Canister', 'moyenne', 100, 300],
            ]),
            'P0455' => $this->entry('Grosse fuite dans le système EVAP', 'Antipollution', 'Le circuit des vapeurs d\'essence n\'est plus étanche du tout. Souvent le bouchon est mal fermé.', 'ok', 'Tu peux rouler. Vérifie le bouchon, une odeur d\'essence doit t\'alerter.', [
                ['Bouchon de réservoir absent ou mal fermé', 0.5, 'Bouchon de réservoir', 'facile', 15, 50],
                ['Durite EVAP débranchée', 0.3, 'Durite EVAP', 'facile', 20, 150],
                ['Électrovanne de purge bloquée', 0.2, 'Électrovanne canister', 'facile', 50, 180],
            ]),
            'P0480' => $this->entry('Circuit de commande du ventilateur de refroidissement 1', 'Refroidissement', 'Le calculateur n\'arrive plus à piloter le ventilateur du radiateur. En bouchon, le moteur peut surchauffer.', 'caution', 'Surveille la température, surtout à l\'arrêt et en ville.', [
                ['Relais de ventilateur HS', 0.4, 'Relais de ventilateur', 'facile', 20, 100],
                ['Motoventilateur HS', 0.4, 'Motoventilateur', 'moyenne', 150, 450],
                ['Fusible grillé', 0.2, 'Fusible', 'facile', 5, 50],
            ]),
            'P0500' => $this->entry('Capteur de vitesse du véhicule', 'Transmission', 'Le calculateur ne reçoit plus la vitesse de la voiture. Compteur, régulateur et passage des rapports peuvent être perturbés.', 'caution', 'Tu peux rouler, mais le compteur peut être faux. Le GPS de Tarmac reste fiable.', [
                ['Capteur de vitesse HS', 0.5, 'Capteur de vitesse', 'facile', 50, 200],
                ['Capteur ABS défectueux', 0.3, 'Capteur ABS', 'facile', 50, 200],
                ['Faisceau abîmé', 0.2, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0505' => $this->entry('Régulation du ralenti', 'Ralenti', 'Le moteur n\'arrive pas à tenir un ralenti stable. Il pompe, monte et descend dans les tours.', 'ok', 'Tu peux rouler. Attention aux calages en manœuvre.', [
                ['Boîtier papillon encrassé', 0.5, 'Boîtier papillon', 'facile', 20, 500],
                ['Prise d\'air', 0.3, 'Durite d\'admission', 'facile', 30, 150],
                ['Moteur pas-à-pas de ralenti HS', 0.2, 'Vanne de ralenti', 'moyenne', 80, 250],
            ]),
            'P0506' => $this->entry('Régime de ralenti trop bas', 'Ralenti', 'Le ralenti est plus bas que prévu, le moteur risque de caler.', 'ok', 'Tu peux rouler.', [
                ['Boîtier papillon encrassé', 0.55, 'Boîtier papillon', 'facile', 20, 500],
                ['Batterie faible ou alternateur fatigué', 0.25, 'Batterie', 'facile', 100, 250],
                ['Prise d\'air', 0.2, 'Durite d\'admission', 'facile', 30, 150],
            ]),
            'P0507' => $this->entry('Régime de ralenti trop haut', 'Ralenti', 'Le ralenti est plus haut que prévu, souvent à cause d\'une entrée d\'air parasite.', 'ok', 'Tu peux rouler.', [
                ['Prise d\'air', 0.5, 'Durite d\'admission', 'facile', 30, 150],
                ['Boîtier papillon encrassé', 0.35, 'Boîtier papillon', 'facile', 20, 500],
                ['Vanne de ralenti HS', 0.15, 'Vanne de ralenti', 'moyenne', 80, 250],
            ]),
            'P0562' => $this->entry('Tension du réseau de bord trop basse', 'Électricité', 'La tension de la batterie est trop basse. La batterie ou l\'alternateur ne suit plus, comme un cœur qui pompe trop faiblement.', 'caution', 'Évite de couper le moteur loin de chez toi : il pourrait ne pas redémarrer.', [
                ['Batterie en fin de vie', 0.5, 'Batterie', 'facile', 100, 250],
                ['Alternateur faible', 0.35, 'Alternateur', 'moyenne', 250, 700],
                ['Cosse oxydée ou masse défaillante', 0.15, 'Cosses / tresse de masse', 'facile', 10, 80],
            ]),
            'P0563' => $this->entry('Tension du réseau de bord trop haute', 'Électricité', 'L\'alternateur envoie trop de tension. Ça peut abîmer la batterie et l\'électronique.', 'caution', 'Fais vérifier vite : une surtension abîme les calculateurs.', [
                ['Régulateur d\'alternateur HS', 0.7, 'Régulateur / alternateur', 'moyenne', 100, 700],
                ['Mauvaise masse', 0.2, 'Tresse de masse', 'facile', 20, 80],
                ['Batterie défectueuse', 0.1, 'Batterie', 'facile', 100, 250],
            ]),
            'P0571' => $this->entry('Circuit du contacteur de frein', 'Électricité', 'Le contacteur de pédale de frein envoie un signal incohérent. Régulateur de vitesse et feux stop peuvent être touchés.', 'caution', 'Vérifie que tes feux stop s\'allument.', [
                ['Contacteur de frein HS', 0.7, 'Contacteur de pédale de frein', 'facile', 20, 100],
                ['Contacteur déréglé', 0.2, 'Contacteur de pédale de frein', 'facile', 0, 50],
                ['Faisceau abîmé', 0.1, 'Faisceau électrique', 'moyenne', 50, 200],
            ]),
            'P0600' => $this->entry('Liaison série de communication', 'Calculateur', 'Le calculateur moteur a perdu la communication avec un autre calculateur.', 'caution', 'Roule prudemment et fais un diagnostic complet.', [
                ['Connecteur de calculateur oxydé', 0.4, 'Connecteur', 'moyenne', 50, 200],
                ['Tension batterie instable', 0.35, 'Batterie', 'facile', 100, 250],
                ['Calculateur défaillant', 0.25, 'Calculateur moteur', 'difficile', 400, 1500],
            ]),
            'P0606' => $this->entry('Défaut interne du calculateur moteur', 'Calculateur', 'Le calculateur moteur détecte une erreur dans son propre fonctionnement. Parfois une simple mise à jour, parfois une panne.', 'caution', 'Roule prudemment : le moteur peut passer en mode dégradé.', [
                ['Tension d\'alimentation instable', 0.35, 'Batterie / masses', 'facile', 20, 250],
                ['Logiciel corrompu (reprogrammation ratée)', 0.35, 'Reprogrammation', 'moyenne', 100, 400],
                ['Calculateur HS', 0.3, 'Calculateur moteur', 'difficile', 400, 1500],
            ]),
            'P0700' => $this->entry('Défaut du système de transmission (boîte automatique)', 'Transmission', 'Le calculateur de boîte a enregistré un défaut. Ce code dit juste « va voir dans la boîte » : il faut lire ses codes à elle.', 'caution', 'Évite les tirs : la boîte peut passer en mode secours.', [
                ['Défaut stocké dans le calculateur de boîte', 0.6, 'Diagnostic boîte', 'moyenne', 50, 150],
                ['Niveau d\'huile de boîte bas', 0.25, 'Vidange de boîte', 'moyenne', 150, 400],
                ['Électrovanne de boîte HS', 0.15, 'Électrovanne de boîte', 'difficile', 200, 800],
            ]),
            'P0715' => $this->entry('Circuit du capteur de vitesse d\'entrée de boîte', 'Transmission', 'La boîte ne sait plus à quelle vitesse tourne son arbre d\'entrée. Les passages deviennent brutaux.', 'caution', 'Roule doucement, la boîte peut se bloquer en mode secours.', [
                ['Capteur de vitesse d\'entrée HS', 0.6, 'Capteur de vitesse de boîte', 'moyenne', 100, 350],
                ['Faisceau abîmé', 0.25, 'Faisceau électrique', 'moyenne', 50, 200],
                ['Mécatronique défaillante', 0.15, 'Mécatronique', 'difficile', 800, 2500],
            ]),
            'P0730' => $this->entry('Rapport de boîte incorrect', 'Transmission', 'Le rapport engagé ne correspond pas à celui demandé : la boîte patine ou n\'enclenche pas correctement.', 'stop', 'Arrête de forcer : une boîte qui patine s\'abîme vite.', [
                ['Niveau ou qualité d\'huile de boîte', 0.4, 'Vidange de boîte', 'moyenne', 150, 400],
                ['Électrovanne de passage HS', 0.35, 'Électrovanne de boîte', 'difficile', 200, 800],
                ['Usure interne (disques, embrayages)', 0.25, 'Révision de boîte', 'difficile', 1500, 4000],
            ]),
            'P0741' => $this->entry('Embrayage du convertisseur de couple : performance', 'Transmission', 'Le convertisseur n\'arrive plus à se verrouiller : il patine, ça chauffe et ça consomme.', 'caution', 'Évite les tirs et les remorquages.', [
                ['Huile de boîte usée', 0.4, 'Vidange de boîte', 'moyenne', 150, 400],
                ['Électrovanne de pontage HS', 0.35, 'Électrovanne de boîte', 'difficile', 200, 800],
                ['Convertisseur usé', 0.25, 'Convertisseur de couple', 'difficile', 800, 2000],
            ]),
        ];

        foreach (range(1, 6) as $cylinder) {
            $definitions[sprintf('P030%d', $cylinder)] = $this->entry(
                "Raté d'allumage détecté sur le cylindre {$cylinder}",
                'Allumage',
                "Le cylindre {$cylinder} rate sa combustion. Le moteur tremble et l'essence non brûlée part dans l'échappement, ce qui peut faire fondre le catalyseur.",
                'stop',
                'Si le voyant clignote, arrête-toi. Échange la bobine avec un cylindre voisin pour voir si le défaut la suit.',
                [
                    ['Bougie usée ou encrassée', 0.35, 'Bougie d\'allumage', 'facile', 15, 60],
                    ['Bobine d\'allumage HS', 0.35, 'Bobine d\'allumage', 'facile', 50, 200],
                    ['Injecteur encrassé', 0.2, 'Injecteur', 'moyenne', 100, 400],
                    ['Perte de compression (soupape, segments)', 0.1, 'Diagnostic compression', 'difficile', 100, 2000],
                ],
            );
        }

        ksort($definitions);

        return $definitions;
    }

    /**
     * @param  list<array{0: string, 1: float, 2: string, 3: string, 4: int, 5: int}>  $causes
     * @return array{title: string, system: string, explanation: string, urgency: string, note: string, causes: list<array{0: string, 1: float, 2: string, 3: string, 4: int, 5: int}>}
     */
    private function entry(string $title, string $system, string $explanation, string $urgency, string $note, array $causes): array
    {
        return compact('title', 'system', 'explanation', 'urgency', 'note', 'causes');
    }
}
