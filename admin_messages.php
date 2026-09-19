<?php

/* Localized messages for Documents 1.2.0 secure mutation endpoints. */

if (isset($_SERVER['PHP_SELF']) && strpos(strtolower($_SERVER['PHP_SELF']), 'admin_messages.php') !== false) {
    die('This file can not be used on its own.');
}

function DOCUMENTS_adminMessage($message)
{
    global $_CONF;

    $message = (string) $message;
    $isFrench = isset($_CONF['language'])
        && strpos(strtolower((string) $_CONF['language']), 'french') === 0;

    if (!$isFrench) {
        return $message;
    }

    $translations = array(
        'Unsupported operation.' => 'Opération non prise en charge.',
        'Invalid category.' => 'Catégorie invalide.',
        'This category still contains documents and cannot be deleted.' => 'Cette catégorie contient encore des documents et ne peut pas être supprimée.',
        'Unable to delete category.' => 'Impossible de supprimer la catégorie.',
        'Category deleted.' => 'Catégorie supprimée.',
        'Category name and URL are required.' => 'Le nom et l’URL de la catégorie sont obligatoires.',
        'This category URL already exists.' => 'Cette URL de catégorie existe déjà.',
        'Unable to save category.' => 'Impossible d’enregistrer la catégorie.',
        'Category saved.' => 'Catégorie enregistrée.',
        'Invalid selection group.' => 'Groupe de sélection invalide.',
        'This selection group is still used by a field.' => 'Ce groupe de sélection est encore utilisé par un champ.',
        'Unable to delete selection group.' => 'Impossible de supprimer le groupe de sélection.',
        'Selection group deleted.' => 'Groupe de sélection supprimé.',
        'Selection group name is required.' => 'Le nom du groupe de sélection est obligatoire.',
        'Unable to save selection group.' => 'Impossible d’enregistrer le groupe de sélection.',
        'Selection group saved.' => 'Groupe de sélection enregistré.',
        'Invalid selection value.' => 'Valeur de sélection invalide.',
        'Unable to delete selection value.' => 'Impossible de supprimer la valeur de sélection.',
        'Selection value deleted.' => 'Valeur de sélection supprimée.',
        'Selection name and group are required.' => 'Le nom et le groupe de sélection sont obligatoires.',
        'Unknown selection group.' => 'Groupe de sélection inconnu.',
        'Unable to save selection value.' => 'Impossible d’enregistrer la valeur de sélection.',
        'Selection value saved.' => 'Valeur de sélection enregistrée.',
        'This selection value is still used by one or more documents.' => 'Cette valeur de sélection est encore utilisée par un ou plusieurs documents.',
        'Invalid field.' => 'Champ invalide.',
        'Unknown field.' => 'Champ inconnu.',
        'Unable to delete field.' => 'Impossible de supprimer le champ.',
        'Field deleted.' => 'Champ supprimé.',
        'Unknown category.' => 'Catégorie inconnue.',
        'Field name and variable name are required.' => 'Le nom du champ et le nom de variable sont obligatoires.',
        'Unsupported or unavailable field type.' => 'Type de champ non pris en charge ou indisponible.',
        'This variable name is already used in the category.' => 'Ce nom de variable est déjà utilisé dans cette catégorie.',
        'A valid selection group is required for select fields.' => 'Un groupe de sélection valide est obligatoire pour les champs de type sélection.',
        'A field already used by documents cannot be moved to another category.' => 'Un champ déjà utilisé par des documents ne peut pas être déplacé vers une autre catégorie.',
        'A field already used by documents cannot change type directly.' => 'Un champ déjà utilisé par des documents ne peut pas changer directement de type.',
        'Unable to save field.' => 'Impossible d’enregistrer le champ.',
        'Field saved.' => 'Champ enregistré.'
    );

    return isset($translations[$message]) ? $translations[$message] : $message;
}
