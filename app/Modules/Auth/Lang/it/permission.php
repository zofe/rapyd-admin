<?php

return [

    'company'                       => 'Azienda',
    'name'                          => 'Nome',
    'permissions'                   => 'Permessi',

    'role_permissions_list'         => 'Lista Ruoli Permessi',
    'role_permissions_view'         => 'Dettaglio Ruolo Permessi',
    'role_permissions_edit'         => 'Modifica Ruolo Permessi',
    'role_permissions_add'          => 'Aggiungi Ruolo Permessi',
    'delete_role_permissions'       => 'Rimuovi Ruolo Permessi',

    'can view routers' => 'Permette la visualizzazione dei router.',
    'can provision routers' => 'Permette il provisioning dei router.',
    'can unprovision routers' => 'Permette l\'eliminazione dei router dal sistema.',
    'can configure notifications' => 'Permette la configurazione delle notifiche sugli eventi dei router.',
    'can manage lan/wan' => 'Consente la gestione della configurazione LAN/WAN.',
    'can manage templates' => 'Consente la gestione dei template per una configurazione e un setup più semplici.',
    'can manage leases' => 'Consente la gestione dei lease.',
    'can manage reboots' => 'Consente la gestione dei riavvii, consentendo la schedulazione di riavvii nel futuro o nell\'immediato.',
    'can use interactive terminal' => 'Consente l\'uso di un terminale interattivo per operazioni a riga di comando.',
    'can view backups' => 'Consente la visualizzazione dei backup.',
    'can manage backups' => 'Consente la gestione dei backup, inclusa l\'esecuzione e il ripristino della configurazione.',
    'can manage continuity' => 'Consente la gestione del servizio Continuity.',
    'can activate continuity' => 'Consente l\'attivazione del servizio Continuity.',
    'can deactivate continuity' => 'Consente la disattivazione del servizio Continuity.',
    'can manage nat' => 'Consente la gestione dei NAT, SNAT e Bypass.',
    'can manage wallet' => 'Consente la gestione del wallet o degli aspetti finanziari del sistema.',
    'can view subscriptions' => 'Consente la visualizzazione delle informazioni sulle sottoscrizioni per il monitoraggio dei servizi.',
    'can manage customers' => 'Consente la gestione dei clienti.',
    'can view customers' => 'Consente la visualizzazione dei profili e dei dettagli dei clienti.',
    'can view users' => 'Consente la visualizzazione dei profili degli utenti della propria azienda.',
    'can manage users' => 'Consente la gestione dei profili degli utenti della propria azienda.',
    'can view tickets' => 'Consente la visualizzazione dei ticket di supporto e del loro stato.',
    'can manage tickets' => 'Consente la gestione dei ticket di supporto',
    'can manage ztna' => 'Consente la gestione della ZTNA',
    'can manage routers' => 'Consente la gestione dei router',
    'can view logs' => 'Consente la visualizzazione dei log delle attività utente',
    'can manage clusters' => 'Consente la richiesta di un nuovo cluster on prem',
    'can manage apis' => 'Consente la visualizzazione e gestione del token API',
    'can manage dns' => 'Consente la visualizzazione e gestione della protezione DNS tramite FlashStart®',
    'can view weathermaps' => 'Consente la visualizzazione della Weathermap',
    'can manage weathermaps' => 'Consente la gestione della Weathermap',


    'explain_role_permissions'      => 'Ogni utente può avere un ruolo, ad ogni ruolo sono applicati determinati permessi.
Puoi editare i permessi abilitati sui ruoli predefiniti o creare ruoli personalizzati per i tuoi utenti.

L\'utente principale/proprietario ha necessariamente tutti i permessi abilitati, e non può essere rimosso.
',

    'tip_role_permission'=>'<i class="fas fa-lightbulb"></i><b>TIP!</b>È possibile trascinare un <span class="badge rounded-pill bg-primary" style="opacity: 0.8;"><i class="fas fa-grip-vertical"></i> Utente</span> in un altra sezione per cambiarne il suo ruolo! (l\'admin non può essere spostato)',
];
