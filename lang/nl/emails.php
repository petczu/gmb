<?php

declare(strict_types=1);

return [
    'greeting' => 'Hoi :name,',
    'signoff' => 'Bedankt,',
    'team' => 'Het Repunio-team',

    'drip_competitors' => [
        'subject' => 'Weet je hoe het bedrijf om de hoek het doet?',
        'intro' => 'Je eigen reviews heb je onder controle. De volgende vraag die elke ondernemer stelt: lig ik voor op de concurrentie of raak ik achterop? Repunio houdt dat voor je in de gaten, met dagelijkse beoordelingen en reviewaantallen voor elk bedrijf op Google.',
        'tip' => 'Duurt twee minuten: open Concurrenten, zoek de naam en voeg hem toe. Vanaf dan zie je wie er voorloopt, met hoeveel, en of jouw beoordeling het bijhoudt.',
        'cta' => 'Voeg je eerste concurrent toe',
    ],

    'location_connected' => [
        'subject' => ':location is gekoppeld',
        'intro' => 'Je locatie :location is nu gekoppeld. We importeren op dit moment de reviews vanaf Google; afhankelijk van hoeveel er zijn kan dat een paar minuten duren.',
        'note' => 'Je krijgt nog een e-mail zodra de reviews binnen zijn.',
        'cta' => 'Locaties bekijken',
    ],

    'location_synced' => [
        'subject' => 'Je reviews zijn binnen',
        'intro' => 'De eerste import is klaar. Dit is wat er binnenkwam:',
        'note' => 'Vanaf nu komen nieuwe reviews automatisch binnen en gelden je automatiseringsregels ervoor.',
        'cta' => 'Open de reviews-inbox',
    ],

    'drip_connect' => [
        'subject' => 'Je account is klaar. Nog één stap',
        'intro' => 'Je Repunio-werkruimte staat klaar, maar is nog leeg: reviews, beoordelingen en rapporten komen allemaal uit je Google Bedrijfsprofiel, en er is er nog geen gekoppeld.',
        'tip' => 'Het duurt ongeveer twee minuten: open Locaties, klik op Koppelen, log in met Google en kies je bedrijf. Je reviews beginnen meteen binnen te komen.',
        'cta' => 'Koppel je locatie',
    ],

    'signup_code' => [
        'subject' => ':code is je registratiecode voor Repunio',
        'intro' => 'Voer deze code in op de registratiepagina om je e-mailadres te bevestigen:',
        'note' => 'De code is :minutes minuten geldig. Als je de aanvraag niet hebt gedaan, kun je deze e-mail gerust negeren.',
    ],

    'beta_received' => [
        'subject' => 'Bedankt! Je toegangsaanvraag is binnen',
        'intro' => 'Bedankt voor je aanmelding! Repunio is momenteel in besloten bèta en we activeren nieuwe accounts in kleine golven.',
        'note' => 'We mailen je zodra je toegang klaarstaat. Er is nu verder niets te doen.',
    ],

    'beta_approved' => [
        'subject' => 'Je toegang tot Repunio staat klaar',
        'intro' => 'Goed nieuws: je account is geactiveerd. Je kunt nu inloggen en alles instellen.',
        'note' => 'Begin met het koppelen van je Google Bedrijfsprofiel, je reviews zijn binnen enkele minuten geïmporteerd.',
        'cta' => 'Open Repunio',
    ],

    'welcome' => [
        'subject' => 'Welkom bij Repunio',
        'intro' => 'Je account is klaar. Repunio helpt je om je Google-reviews te verzamelen, erop te reageren en erover te rapporteren, allemaal op één plek.',
        'next' => 'Volgende stap: koppel je eerste locatie en kies een abonnement om je gratis proefperiode van 14 dagen te starten.',
        'cta' => 'Open Repunio',
    ],

    'trial_ending' => [
        'subject' => 'Je gratis proefperiode eindigt over :days dagen',
        'intro' => 'Je gratis proefperiode bij Repunio eindigt op :date. Voeg nu een betaalmethode toe zodat er niets stopt: je reviews blijven synchroniseren en AI-reacties blijven werken.',
        'note' => 'Er wordt pas afgeschreven als de proefperiode eindigt, en je kunt op elk moment opzeggen.',
        'cta' => 'Betaalmethode toevoegen',
    ],

    'payment_succeeded' => [
        'subject' => 'Betaling ontvangen',
        'intro' => 'We hebben je betaling van :amount ontvangen. Je Repunio-abonnement is actief.',
        'cta' => 'Facturering bekijken',
    ],

    'payment_failed' => [
        'subject' => 'Betaling mislukt, actie nodig',
        'intro' => 'We konden je laatste betaling niet verwerken. Je account blijft nog :days dagen werken, werk je facturering bij om onderbreking te voorkomen.',
        'cta' => 'Facturering bijwerken',
    ],

    'subscription_canceled' => [
        'subject' => 'Je abonnement wordt opgezegd',
        'intro' => 'Je Repunio-abonnement is opgezegd. Je houdt volledige toegang tot :date, daarna wordt het niet verlengd.',
        'note' => 'Toch van gedachten veranderd? Je kunt het voor die datum op elk moment hervatten, zonder kosten.',
        'cta' => 'Abonnement hervatten',
    ],

    'subscription_resumed' => [
        'subject' => 'Je abonnement is weer actief',
        'intro' => 'Je Repunio-abonnement is hervat en wordt gewoon weer verlengd. Verder niets te doen.',
        'cta' => 'Facturering bekijken',
    ],

    'ai_limit' => [
        'subject' => 'Je hebt al je AI-reacties voor deze maand gebruikt',
        'intro' => 'Je hebt je maandelijkse limiet voor AI-reacties op het :plan-abonnement bereikt. Upgrade voor een hogere limiet, of blijf handmatig reageren tot volgende maand.',
        'cta' => 'Bekijk abonnementen',
    ],

    'auto_recharge_failed' => [
        'subject' => 'AI-bijvulbetaling mislukt',
        'intro' => 'We probeerden je AI-reacties automatisch bij te vullen, maar de betaling ging niet door. Werk je kaart bij zodat automatisch bijvullen kan blijven werken.',
        'cta' => 'Facturering bijwerken',
    ],

    'new_reviews' => [
        'subject' => ':count nieuwe review(s) voor je bedrijf',
        'intro' => 'Je hebt :count nieuwe review(s) voor :location.',
        'col_author' => 'Auteur',
        'col_rating' => 'Beoordeling',
        'col_location' => 'Locatie',
        'col_review' => 'Review',
        'cta' => 'Reviews bekijken',
    ],

    'account_disconnected' => [
        'subject' => 'Actie nodig: je Google-koppeling werkt niet meer',
        'intro' => 'De Google-koppeling voor ":account" werkt niet meer, waardoor je reviews niet langer synchroniseren.',
        'detail' => 'Koppel het account opnieuw om het synchroniseren van reviews en het plaatsen van reacties te hervatten.',
        'cta' => 'Opnieuw koppelen',
    ],

    'sync_restored' => [
        'subject' => 'Je Google-koppeling is terug',
        'intro' => 'Goed nieuws: de koppeling voor ":account" is terug en het synchroniseren is hervat. Je reviews zijn weer up-to-date.',
        'cta' => 'Open Repunio',
    ],

    'negative_review' => [
        'subject' => 'Review met :rating★ vraagt om je aandacht',
        'intro' => 'Een nieuwe review voor :business vraagt om je aandacht.',
        'col_author' => 'Auteur',
        'col_rating' => 'Beoordeling',
        'col_review' => 'Review',
        'cta' => 'Nu reageren',
    ],

    'reply_failed' => [
        'subject' => 'We konden je reactie niet plaatsen',
        'intro' => 'We probeerden een reactie te plaatsen op een review voor :business, maar dat is mislukt.',
        'col_author' => 'Auteur',
        'col_review' => 'Review',
        'detail' => 'Probeer de reactie opnieuw te plaatsen vanuit de app.',
        'detail_retry' => 'Dit lijkt tijdelijk, dus we proberen het de komende uren automatisch opnieuw. Je hoeft niets te doen. Als het blijft mislukken, vind je het terug bij Reviews → Mislukt.',
        'detail_not_found' => 'Google geeft aan dat deze review niet meer bestaat. Mogelijk is die door de auteur verwijderd of door Google gefilterd. Niets te doen: het concept is opzijgezet en wordt niet opnieuw geprobeerd.',
        'detail_unauthorized' => 'De Google-koppeling is niet gemachtigd om voor deze locatie te reageren, dus we proberen het niet opnieuw. Koppel het account opnieuw en plaats de reactie daarna vanuit de app.',
        'cta' => 'Goedkeuringen openen',
    ],

    'post_failed' => [
        'subject' => 'We konden je Google-bericht niet publiceren',
        'intro' => 'We probeerden een Google-bericht te publiceren voor :business, maar dat is mislukt. Het bericht staat in je kalender met de foutmelding.',
        'detail' => 'Probeer het bericht opnieuw te publiceren vanuit de app.',
        'detail_reason' => 'Reden: :reason',
        'cta' => 'Berichten openen',
    ],

    'approvals_pending' => [
        'subject' => ':count :replies wachten op goedkeuring',
        'intro' => 'Je hebt :count :replies die wachten op je goedkeuring. Bekijk ze en keur ze goed zodat ze geplaatst worden.',
        'reply_word' => '{1}reactie|[2,*]reacties',
        'reply_label' => 'Voorgestelde reactie',
        'cta' => 'Goedkeuringen bekijken',
    ],

    'review_goal' => [
        'subject_mid' => 'Je reviewdoel: hoe de maand verloopt',
        'subject_recap' => 'Reviewoverzicht voor :month',
        'intro_mid_ahead' => 'Mooi tempo! Je hebt deze maand :actual nieuwe reviews, boven de :expected die nu verwacht werden (doel :goal). Ga zo door.',
        'intro_mid_on_track' => 'Je ligt op schema: :actual nieuwe reviews deze maand, vlak bij de :expected die nu verwacht werden (doel :goal).',
        'intro_mid_behind' => 'Een duwtje: je hebt deze maand :actual nieuwe reviews, onder de :expected die nu verwacht werden (doel :goal). Een beetje extra inzet helpt.',
        'intro_recap' => 'Zo is :month geëindigd: :actual nieuwe reviews tegenover een doel van :goal.',
        'col_location' => 'Locatie',
        'col_goal' => 'Doel',
        'col_so_far' => 'Tot nu toe',
        'col_projected' => 'Prognose',
        'col_pace' => 'Tempo',
        'col_got' => 'Behaald',
        'col_vs_goal' => 'vs doel',
        'col_vs_prev' => 'vs vorige maand',
        'status_ahead' => 'Voor',
        'status_on_track' => 'Op schema',
        'status_behind' => 'Achter',
        'cta' => 'Reviews bekijken',
    ],

    'coaching' => [
        'subject' => 'Je reviewdoel: laten we het volhouden',
        'intro_almost' => 'Zo dichtbij! Nog maar :remaining te gaan om je doel van :goal deze maand te halen. Het gaat je lukken!',
        'intro_behind' => 'Je staat op :actual van :goal deze maand. Een gestage inspanning deze week brengt je weer op tempo. Hier zijn een paar ideeën.',
        'intro_on_track' => 'Goed bezig! :actual van :goal en precies op tempo. Een paar verzoeken deze week houden de vaart erin.',
        'intro_ahead' => 'Mooie vaart! :actual van :goal, voor op schema. Houd het gaande met deze ideeën.',
        'steady' => 'Eén ding: spreid je verzoeken over de dagen. Een plotselinge stroom reviews oogt verdacht voor Google en kan gefilterd worden. Regelmaat wint.',
        'cta' => 'Reviews openen',
    ],

    'goal_reached' => [
        'subject' => 'Doel behaald! :goal reviews deze maand! 🎉',
        'intro' => 'Gefeliciteerd! Je hebt deze maand je doel van :goal nieuwe reviews gehaald! Dat is echt momentum voor je reputatie.',
        'note' => 'Houd de gewoonte in een gestaag tempo vast en volgende maand gaat het nog makkelijker.',
        'cta' => 'Reviews openen',
    ],

    'review_anomaly' => [
        'subject' => 'Let op: :count ding(en) om te controleren bij je reviews',
        'intro' => 'We zagen iets bij je reviews wat een blik waard is:',
        'stalled' => 'geen nieuwe reviews sinds :days dagen, terwijl het normaal actief is.',
        'negative_streak' => ':count reviews met weinig sterren binnen 3 dagen. Reageer snel om de schade te beperken.',
        'spike' => 'ongebruikelijke piek: :recent reviews in 7 dagen (normaal ongeveer :baseline per week). Goed nieuws, of het waard om op spam te controleren.',
        'rating_drop' => 'de beoordeling zakt: :recent★ recent tegenover :prior★ eerder.',
        'cta' => 'Reviews openen',
    ],

    'invite' => [
        'subject' => 'Je bent uitgenodigd om je aan te sluiten bij :workspace op Repunio',
        'greeting' => 'Hoi,',
        'intro' => ':inviter nodigt je uit om je aan te sluiten bij :workspace op Repunio als :role.',
        'note' => 'Deze uitnodiging verloopt over 14 dagen. Als je hem niet verwachtte, kun je deze e-mail negeren.',
        'cta' => 'Uitnodiging accepteren',
    ],

    // Onboarding drip series (product education)
    'drip_inbox' => [
        'subject' => 'Elke review, één inbox',
        'intro' => 'Alle reviews van je locaties komen binnen in één inbox. Filter op beoordeling, locatie of onbeantwoord, en reageer in twee klikken.',
        'tip' => 'Probeer het nu: open een review en druk op Genereren met AI. Je krijgt een kant-en-klaar concept in jouw toon dat je vóór publicatie kunt aanpassen.',
        'cta' => 'Open je reviews',
    ],
    'drip_automation' => [
        'subject' => 'Zet reacties op de automatische piloot',
        'intro' => 'Maak een AI-agent die je bedrijf en toon kent, en laat regels voor automatisch antwoord routinematige reviews voor je beantwoorden.',
        'tip' => 'Nog niet klaar voor de volledige automatische piloot? Gebruik de goedkeuringswachtrij: de AI stelt op, jij keurt goed met één klik.',
        'cta' => 'Automatiseringen instellen',
    ],
    'drip_growth' => [
        'subject' => 'Verzamel deze maand meer reviews',
        'intro' => 'Stel per locatie een maandelijks reviewdoel in en wij houden het tempo bij, vieren mijlpalen en waarschuwen je bij anomalieën.',
        'tip' => 'Maak je pagina voor het verzamelen van reviews: een korte link en QR-code die tevreden klanten rechtstreeks naar je reviewformulier op Google of TripAdvisor sturen.',
        'cta' => 'Maak je reviewpagina',
    ],
    'drip_reports' => [
        'subject' => 'Rapporten die echt gelezen worden',
        'intro' => 'Bouw een prestatierapport uit blokken: KPI\'s, AI-samenvatting, vermeldingen van medewerkers, thema\'s. Download als pdf of deel een link.',
        'tip' => 'Eén keer instellen, maandelijks versturen: plan het rapport in en het komt automatisch in de inbox terecht, in het Engels of Duits.',
        'cta' => 'Bouw een rapport',
    ],
    'drip_team' => [
        'subject' => 'Betrek je team',
        'intro' => 'Nodig teamleden uit met rollen, of voeg gasten toe die alleen meldingen en rapporten ontvangen, zonder inloggen.',
        'tip' => 'Bepaal onder Instellingen wie welke e-mail krijgt, en stuur meldingen van nieuwe reviews naar de mensen die ze afhandelen.',
        'cta' => 'Nodig je team uit',
    ],
    'drip_member' => [
        'subject' => 'Wegwijs in Repunio',
        'intro' => 'Je bent toegevoegd aan een werkruimte. De reviews-inbox is waar het werk gebeurt: filteren, reageren, klaar.',
        'tip' => 'Stel de taal van de interface en de e-mails in je profiel in, zodat alles binnenkomt zoals jij het wilt.',
        'cta' => 'Open Repunio',
    ],
    'drip_unsubscribe' => 'Te veel tips? :link',
    'drip_unsubscribe_link' => 'Uitschrijven voor deze e-mails',

    'unsubscribed_title' => 'Je bent uitgeschreven',
    'unsubscribed_body' => 'Je ontvangt geen producttips en onboarding-e-mails meer. Belangrijke e-mails over je account en facturering komen nog steeds binnen. Van gedachten veranderd? Zet ze weer aan in :link.',
    'unsubscribed_profile' => 'je profiel',
];
