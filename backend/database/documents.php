<?php

return json_decode(<<<'JSON'
[
  {
    "slug": "signaler-un-incident",
    "title": "Signaler un incident informatique",
    "category": "Assistance",
    "summary": "Les informations à préparer pour obtenir une assistance efficace.",
    "blocks": [
      {
        "type": "section",
        "title": "Décrire ce qui ne fonctionne pas",
        "text": "Notez le nom de l’outil, l’heure de l’incident et le message exact. Décrivez l’action que vous réalisiez et le résultat attendu."
      },
      {
        "type": "section",
        "title": "Faire une première vérification",
        "text": "Vérifiez les branchements et votre connexion au réseau interne. Enregistrez votre travail avant de fermer une application ou de redémarrer le poste."
      },
      {
        "type": "checklist",
        "title": "Préparer une demande utile",
        "items": [
          "Indiquer le poste concerné et votre service.",
          "Décrire les étapes permettant de reproduire le problème.",
          "Préciser l’impact : une personne, une équipe ou un service bloqué.",
          "Joindre une capture lisible sans données confidentielles."
        ]
      },
      {
        "type": "note",
        "title": "Votre mot de passe reste personnel",
        "text": "Ne joignez jamais de mot de passe, de code de session ou de document confidentiel à une demande d’assistance."
      },
      {
        "type": "section",
        "title": "Suivre la résolution",
        "text": "Transmettez la demande par votre canal interne d’assistance. Conservez sa référence, restez disponible pour les vérifications et confirmez le retour au fonctionnement normal."
      }
    ]
  },
  {
    "slug": "consulter-la-documentation",
    "title": "Consulter Atlas Documentation",
    "category": "Prise en main",
    "summary": "Accéder au portail interne, retrouver un guide et personnaliser sa lecture.",
    "blocks": [
      {
        "type": "section",
        "title": "Accéder au portail",
        "text": "Depuis un poste connecté au réseau interne, ouvrez https://atlas.u5. Connectez-vous avec le compte attribué par le service informatique."
      },
      {
        "type": "section",
        "title": "Trouver le bon guide",
        "text": "Recherchez un sujet dans la bibliothèque ou parcourez les catégories. Vérifiez le résumé et la date de mise à jour avant de suivre les étapes."
      },
      {
        "type": "section",
        "title": "Garder vos repères",
        "text": "Cliquez sur le signet pour conserver un guide dans Mes favoris. Le sommaire mène directement à une section. Le bouton d’impression prépare une version adaptée au papier."
      },
      {
        "type": "section",
        "title": "Personnaliser votre lecture",
        "text": "Cliquez sur votre nom en haut à droite pour ouvrir Mon profil. Vous pouvez choisir une photo, compléter les informations facultatives et agrandir le texte des guides."
      },
      {
        "type": "note",
        "title": "Fermer votre session",
        "text": "Déconnectez-vous sur un poste partagé. Les comptes sont créés uniquement par un administrateur ; signalez un problème d’accès par votre canal interne habituel."
      }
    ]
  },
  {
    "slug": "demarrer-son-poste",
    "title": "Bien démarrer sur son poste",
    "category": "Prise en main",
    "summary": "Une routine simple pour commencer sa journée avec un poste prêt à travailler.",
    "blocks": [
      {
        "type": "section",
        "title": "Vérifier votre espace de travail",
        "text": "Contrôlez l’alimentation, l’écran et les périphériques utiles. Connectez le poste au réseau interne et utilisez uniquement les équipements attribués par votre service informatique."
      },
      {
        "type": "section",
        "title": "Ouvrir votre session personnelle",
        "text": "Utilisez votre compte individuel. Vérifiez que votre environnement habituel apparaît et que les dossiers de travail internes sont disponibles."
      },
      {
        "type": "checklist",
        "title": "Avant de commencer",
        "items": [
          "Ouvrir uniquement les outils nécessaires.",
          "Vérifier le dossier où seront enregistrés les fichiers de la journée.",
          "Consulter les consignes de votre équipe.",
          "Signaler une anomalie persistante au service informatique."
        ]
      },
      {
        "type": "note",
        "title": "Une mise à jour en cours ?",
        "text": "Laissez terminer les opérations affichées par le poste. Évitez d’interrompre l’alimentation ; si le message reste bloqué, demandez conseil au service informatique."
      },
      {
        "type": "section",
        "title": "Terminer votre journée",
        "text": "Enregistrez vos documents dans leur emplacement prévu, fermez les applications et appliquez la consigne de votre équipe pour la fermeture de session ou l’arrêt du poste."
      }
    ]
  },
  {
    "slug": "organiser-fichiers",
    "title": "Organiser les fichiers partagés",
    "category": "Bureautique",
    "summary": "Retrouver ses documents facilement et travailler sur la bonne version.",
    "blocks": [
      {
        "type": "section",
        "title": "Choisir l’emplacement prévu",
        "text": "Utilisez le dossier interne partagé désigné pour votre service ou projet. Un fichier conservé uniquement sur le bureau de votre poste peut être inaccessible à vos collègues."
      },
      {
        "type": "section",
        "title": "Nommer les fichiers clairement",
        "text": "Choisissez un nom court comprenant la date, le sujet et la version lorsque cela aide votre équipe. Conservez la convention utilisée dans le dossier."
      },
      {
        "type": "code",
        "title": "Exemple de nom lisible",
        "text": "2026-10-13_compte-rendu_equipe_v01.docx"
      },
      {
        "type": "section",
        "title": "Éviter les doublons",
        "text": "Avant de créer une copie, recherchez le document existant. Indiquez clairement la version de référence et rangez les anciennes versions selon la règle du service."
      },
      {
        "type": "checklist",
        "title": "Avant d’enregistrer",
        "items": [
          "Vérifier le dossier et le nom du fichier.",
          "Confirmer que la version de travail est la bonne.",
          "Éviter les informations sensibles dans le nom.",
          "Vérifier que le document s’ouvre après l’enregistrement."
        ]
      },
      {
        "type": "note",
        "title": "Les droits suivent le besoin",
        "text": "Si un collègue ne peut pas ouvrir un fichier, demandez une vérification des droits. N’utilisez pas un autre compte et ne changez pas les permissions sans autorisation."
      }
    ]
  },
  {
    "slug": "proteger-sa-session",
    "title": "Protéger sa session et ses accès",
    "category": "Sécurité",
    "summary": "Les bons réflexes pour protéger son poste et conserver un accès personnel.",
    "blocks": [
      {
        "type": "section",
        "title": "Verrouiller avant de s’éloigner",
        "text": "Verrouillez votre session chaque fois que vous quittez le poste, même pour quelques minutes. Sur un poste Windows, le raccourci Windows + L permet de le faire rapidement."
      },
      {
        "type": "section",
        "title": "Conserver ses accès personnels",
        "text": "Ne partagez pas votre mot de passe et n’utilisez pas le compte d’un collègue. Suivez les consignes internes pour choisir et conserver vos identifiants."
      },
      {
        "type": "note",
        "title": "Aucune demande légitime de partage",
        "text": "Une personne qui vous aide n’a pas besoin que vous lui envoyiez votre mot de passe. Si une demande vous paraît inhabituelle, faites-la vérifier par le service informatique."
      },
      {
        "type": "checklist",
        "title": "Sur un poste partagé",
        "items": [
          "Se connecter avec son propre compte.",
          "Ne pas enregistrer son mot de passe dans le navigateur partagé.",
          "Fermer les documents contenant des informations sensibles.",
          "Se déconnecter du portail et fermer sa session."
        ]
      },
      {
        "type": "section",
        "title": "En cas de doute",
        "text": "Signalez rapidement un accès inhabituel ou une divulgation possible. Précisez les faits observés ; le service informatique vous indiquera les mesures à appliquer."
      }
    ]
  },
  {
    "slug": "impression-interne",
    "title": "Utiliser l’impression interne",
    "category": "Bureautique",
    "summary": "Choisir la bonne imprimante et récupérer ses documents en sécurité.",
    "blocks": [
      {
        "type": "section",
        "title": "Préparer le document",
        "text": "Utilisez l’aperçu avant impression pour vérifier les pages, les marges et l’orientation. Sélectionnez seulement les pages nécessaires."
      },
      {
        "type": "section",
        "title": "Choisir l’imprimante prévue",
        "text": "Sélectionnez l’équipement interne de votre service et vérifiez les options proposées : format, recto verso et couleur selon vos besoins."
      },
      {
        "type": "checklist",
        "title": "Lancer l’impression",
        "items": [
          "Confirmer le nombre d’exemplaires.",
          "Vérifier le nom de l’imprimante.",
          "Utiliser la libération sécurisée si elle est proposée.",
          "Récupérer les pages immédiatement."
        ]
      },
      {
        "type": "note",
        "title": "Documents confidentiels",
        "text": "Ne laissez pas de documents sensibles dans le bac. Respectez les règles internes de conservation et de destruction."
      },
      {
        "type": "section",
        "title": "Si rien ne sort",
        "text": "Vérifiez la file d’attente et le message affiché sur l’équipement. Évitez de relancer plusieurs fois la même demande ; notez le nom de l’imprimante pour demander de l’aide."
      }
    ]
  },
  {
    "slug": "preparer-reunion",
    "title": "Préparer une réunion en salle",
    "category": "Vie d’équipe",
    "summary": "Vérifier la salle, le matériel et les documents avant l’arrivée des participants.",
    "blocks": [
      {
        "type": "section",
        "title": "Prévoir les éléments utiles",
        "text": "Confirmez la réservation selon l’organisation interne. Préparez un ordre du jour et les documents à présenter dans leur dossier partagé."
      },
      {
        "type": "section",
        "title": "Vérifier le matériel",
        "text": "Arrivez quelques minutes avant le début. Contrôlez l’écran, la connexion du poste et les accessoires nécessaires. Affichez un document de vérification sans informations sensibles."
      },
      {
        "type": "checklist",
        "title": "Avant l’accueil des participants",
        "items": [
          "Vérifier que les fichiers utiles s’ouvrent.",
          "Confirmer que le contenu affiché est lisible.",
          "Fermer les fenêtres et notifications sans rapport avec la réunion.",
          "Prévoir qui prendra les notes et les décisions."
        ]
      },
      {
        "type": "section",
        "title": "Clore la réunion",
        "text": "Rangez les documents utilisés et conservez le compte rendu dans le dossier prévu. Fermez la session ouverte en salle et laissez le matériel disponible pour l’équipe suivante."
      },
      {
        "type": "note",
        "title": "Un équipement indisponible",
        "text": "Signalez le nom de la salle, l’équipement concerné et le symptôme. Ne modifiez pas les branchements fixes sans consigne du service informatique."
      }
    ]
  },
  {
    "slug": "message-suspect",
    "title": "Réagir à un message suspect",
    "category": "Sécurité",
    "summary": "Identifier une demande inhabituelle et la signaler par le bon canal interne.",
    "blocks": [
      {
        "type": "section",
        "title": "Prendre le temps de vérifier",
        "text": "Une demande urgente, un changement inhabituel de coordonnées ou une pièce jointe inattendue mérite une vérification. Ne vous fiez pas uniquement au nom affiché de l’expéditeur."
      },
      {
        "type": "section",
        "title": "Éviter une action précipitée",
        "text": "N’ouvrez pas la pièce jointe et ne saisissez pas d’identifiants si vous avez un doute. Vérifiez la demande par votre canal interne habituel, indépendamment du message reçu."
      },
      {
        "type": "checklist",
        "title": "Préparer le signalement",
        "items": [
          "Conserver le message sans le redistribuer largement.",
          "Noter l’heure de réception et l’expéditeur affiché.",
          "Préciser si vous avez ouvert un élément ou saisi des informations.",
          "Transmettre les faits au service informatique par le canal interne prévu."
        ]
      },
      {
        "type": "note",
        "title": "Si vous avez déjà agi",
        "text": "Signalez-le sans attendre, même si aucun problème n’est visible. Décrivez précisément l’action réalisée et suivez les consignes du service informatique."
      },
      {
        "type": "section",
        "title": "Suivre les consignes",
        "text": "Attendez les instructions pour conserver ou supprimer le message. Ne tentez pas de tester vous-même le contenu suspect."
      }
    ]
  },
  {
    "slug": "retrouver-fichier",
    "title": "Retrouver un fichier manquant",
    "category": "Assistance",
    "summary": "Vérifier l’emplacement et préparer une demande de restauration si nécessaire.",
    "blocks": [
      {
        "type": "section",
        "title": "Chercher la version attendue",
        "text": "Vérifiez le nom, le dossier et la date de modification. Recherchez dans les emplacements internes que vous utilisez habituellement, sans déplacer les fichiers trouvés."
      },
      {
        "type": "section",
        "title": "Vérifier un déplacement ou une suppression",
        "text": "Consultez les fonctions de recherche et de récupération proposées par l’outil. Vérifiez avec votre équipe si le fichier a été renommé ou archivé."
      },
      {
        "type": "checklist",
        "title": "Pour demander une restauration",
        "items": [
          "Indiquer le chemin ou le dossier concerné.",
          "Préciser le nom du fichier et sa dernière date de présence connue.",
          "Décrire la version dont vous avez besoin.",
          "Préciser l’impact et la date à laquelle vous devez le retrouver."
        ]
      },
      {
        "type": "note",
        "title": "Préserver les données disponibles",
        "text": "N’écrasez pas une version existante et évitez de recréer des fichiers au même emplacement pendant l’analyse. Une restauration dépend des sauvegardes réellement disponibles."
      },
      {
        "type": "section",
        "title": "Vérifier le fichier récupéré",
        "text": "Ouvrez le document dans son outil habituel, contrôlez son contenu et confirmez au service informatique que la version récupérée correspond à votre besoin."
      }
    ]
  },
  {
    "slug": "preparer-absence",
    "title": "Préparer une absence et la passation",
    "category": "Vie d’équipe",
    "summary": "Laisser à son équipe les informations utiles sans partager ses identifiants.",
    "blocks": [
      {
        "type": "section",
        "title": "Rassembler les repères",
        "text": "Identifiez les dossiers en cours, les échéances et les personnes qui assureront le relais. Rangez les documents utiles dans l’emplacement partagé autorisé."
      },
      {
        "type": "section",
        "title": "Préparer une note de passation",
        "text": "Pour chaque sujet, indiquez le statut, la prochaine action et l’emplacement des documents de référence. Limitez la note aux informations nécessaires à l’équipe."
      },
      {
        "type": "checklist",
        "title": "Avant le départ",
        "items": [
          "Vérifier que la personne de relais possède ses propres accès.",
          "Confirmer les échéances et priorités.",
          "Ranger les documents et enregistrer les modifications.",
          "Fermer les sessions et sécuriser le poste."
        ]
      },
      {
        "type": "note",
        "title": "Aucun partage de compte",
        "text": "La passation ne nécessite pas de transmettre un mot de passe. Demandez à un administrateur de vérifier les droits du relais si nécessaire."
      },
      {
        "type": "section",
        "title": "Au retour",
        "text": "Consultez la note mise à jour et les documents de référence. Vérifiez les décisions prises et reprenez les sujets avec la personne de relais."
      }
    ]
  }
]
JSON, true, 32, JSON_THROW_ON_ERROR);
