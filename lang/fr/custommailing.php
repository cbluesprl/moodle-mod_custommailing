<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file manages all the fr strings
 *
 * @package    mod_custommailing
 * @author     jeanfrancois@cblue.be
 * @copyright  2021 CBlue SPRL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Custom Mailing';

$string['andtargetactivitynotcompleted'] = "et que l'activité déclencheuse n'est pas déjà terminée par l'utilisateur";
$string['atactivitycompleted'] = "Lorsque l'activité déclencheuse est complétée";
$string['atcourseenrol'] = "A l'inscripiton d'un cours";
$string['atfirstlaunch'] = "Au premier lancement de l'activité déclencheuse";
$string['certificate'] = "Certificat";
$string['confirmdelete'] = 'Êtes-vous sûr de supprimer : {$a}';
$string['course'] = "Formation";
$string['coursecompletionenabled'] = "Attention : La complétion du cours a été activée.";
$string['coursecompletionnotenabled'] = "Erreur : L'activité a été ajoutée mais la complétion du cours n'a pas pu être activée.";
$string['courseenroldate'] = "jour(s) après la date d'inscription au cours";
$string['courselastaccess'] = "jour(s) après la date de dernière connexion au cours";
$string['createmailing'] = "Créer le mailing";
$string['createnewmailing'] = 'Créer un nouveau mailing';
$string['crontask'] = "Tâche programmée";
$string['customcert'] = "Certificat";
$string['customcert_help'] = "Un email avec le certificat en pièce jointe sera envoyé à tous les utilisateurs remplissant les conditions d'obtention du certificat";
$string['custommailingname'] = "Nom";
$string['daysafter'] = 'jour(s) après :';
$string['debugmode'] = "Mode debug";
$string['debugmode_help'] = "Délai d'envoi en minutes au lieu de jours";
$string['disabled'] = "Inactif";
$string['enabled'] = "Actif";
$string['firstlaunch'] = "jour(s) après la date du premier lancement";
$string['lastlaunch'] = "jour(s) après la date du dernier lancement";
$string['log_mailing_failed'] = 'Echoué';
$string['log_mailing_idle'] = 'En attente';
$string['log_mailing_processing'] = 'En traitement';
$string['log_mailing_sent'] = 'Envoyé';
$string['log_mailing_unknown'] = 'Unknown';
$string['logtable'] = "Journal des envois";
$string['mailingadded'] = "Mailing ajouté";
$string['mailingcontent'] = "Contenu";
$string['mailingcontent_help'] = 'Vous pouvez utiliser les variables suivantes dans le texte du mail :
<ul>
<li>%firstname%</li>
<li>%lastname%</li>
</ul>';
$string['mailingdeleted'] = 'Mailing supprimé';
$string['mailinglang'] = 'Langue';
$string['mailingname'] = 'Nom';
$string['mailingsubject'] = "Sujet";
$string['mailingtargetactivitystatuscomplete'] = "Activité déclencheuse doit être terminée";
$string['mailingtargetactivitystatusincomplete'] = "Activité déclencheuse ne doit pas être terminée";
$string['mailingupdated'] = "Mailing mis à jour";
$string['module'] = "Scorm";
$string['modulename'] = 'Mailing personnalisé';
$string['modulenameplural'] = "Mailing personnalisés";
$string['pluginadministration'] = 'Admnistration Mailing personnalisé';
$string['retroactive'] = "Rétroactif";
$string['retroactive_help'] = "Effet rétroactif des conditions d'envoi du mailing. Attention qu'activer cette option enverra un nouvel e-mail à TOUS les utilisateurs inscrits au cours remplissant les conditions.";
$string['sendmailing'] = "Envoyer le mailing";
$string['select'] = "Sélectionnez";
$string['selectsource'] = "Source";
$string['settings'] = "Paramètres";
$string['starttime'] = "Heure d'envoi";
$string['targetactivitynotfound'] = "L'activité déclencheuse n'existe pas";
$string['targetmoduleid'] = 'Activité déclencheuse';
$string['timecreated'] = 'Date et heure de création';
$string['timemodified'] = 'Date et heure de modification';
$string['updatemailing'] = "Mettre à jour le mailing";

$string['privacy:metadata'] = 'Le plugin conserve la date, le statut et l\'identifiant du destinataire des emails envoyés par chaque mailing';
$string['privacy:metadata:custommailing_logs'] = 'Custom Mailing Logs';
$string['privacy:metadata:custommailingmailingid'] = 'Identifiant du Mailing';
$string['privacy:metadata:emailtouserid'] = 'Identifiant de l\'utilisateur';
$string['privacy:metadata:emailstatus'] = 'Statut d\'envoi de l\'email';
$string['privacy:metadata:timecreated'] = 'Date et heure de création';
$string['privacy:metadata:timemodified'] = 'Date et heure de modification';