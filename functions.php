<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Session, escaping, language, URL, and redirect helpers.
function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function supported_languages(): array
{
    return [
        'fr' => 'Francais',
        'en' => 'English',
        'ar' => 'العربية',
    ];
}

function normalize_language(?string $language): string
{
    $language = strtolower(trim((string)$language));
    return array_key_exists($language, supported_languages()) ? $language : 'fr';
}

function set_current_language(string $language): void
{
    start_app_session();
    $language = normalize_language($language);
    $_SESSION['language'] = $language;
    setcookie('edupay_language', $language, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function current_language(): string
{
    start_app_session();
    if (isset($_SESSION['language'])) {
        return normalize_language((string)$_SESSION['language']);
    }
    if (isset($_COOKIE['edupay_language'])) {
        $language = normalize_language((string)$_COOKIE['edupay_language']);
        $_SESSION['language'] = $language;
        return $language;
    }

    return 'fr';
}

function language_direction(?string $language = null): string
{
    return ($language ?? current_language()) === 'ar' ? 'rtl' : 'ltr';
}

function tr(string $text, ?string $language = null): string
{
    $language = normalize_language($language ?? current_language());
    if ($language === 'fr') {
        return $text;
    }

    $translations = translation_catalog();
    return $translations[$language][$text] ?? $text;
}

function translate_rendered_html(string $html, ?string $language = null): string
{
    $language = normalize_language($language ?? current_language());
    if ($language === 'fr') {
        return $html;
    }

    $translations = translation_catalog()[$language] ?? [];
    uksort($translations, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
    return strtr($html, $translations);
}

function translation_catalog(): array
{
    static $catalog = null;
    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = [
        'en' => [
            'Francais' => 'French',
            'Langue' => 'Language',
            'Nuit' => 'Night',
            'Jour' => 'Day',
            'Activer le mode nuit' => 'Turn on night mode',
            'Activer le mode jour' => 'Turn on day mode',
            'Mode nuit' => 'Night mode',
            'Mode jour' => 'Day mode',
            'Notifications' => 'Notifications',
            'Tout lu' => 'Mark all read',
            'Chargement...' => 'Loading...',
            'Aucune notification.' => 'No notifications.',
            'Deconnexion' => 'Logout',
            'EduPay+ gestion comptable des etudiants' => 'EduPay+ student accounting management',
            'Tableau de bord' => 'Dashboard',
            'Etudiants' => 'Students',
            'Frais' => 'Fees',
            'Factures' => 'Invoices',
            'Paiements' => 'Payments',
            'Recus' => 'Receipts',
            'Rapports' => 'Reports',
            'Bourse' => 'Stipend',
            'Bourses' => 'Stipends',
            'Profil' => 'Profile',
            'Agent' => 'Agent',
            'Etudiant' => 'Student',
            'Accueil' => 'Home',
            'Bienvenue' => 'Welcome',
            'La plateforme de gestion comptable dediee aux etudiants et agents.' => 'The accounting management platform for students and agents.',
            'Connexion' => 'Login',
            'Inscription' => 'Registration',
            'Gestion comptable des etudiants' => 'Student accounting management',
            'Acces etudiants et agents comptables.' => 'Student and accounting agent access.',
            'Matricule' => 'ID number',
            'Mot de passe' => 'Password',
            'Se connecter' => 'Log in',
            'Pas encore de compte ?' => 'No account yet?',
            'Inscrivez-vous' => 'Sign up',
            'Deja un compte ?' => 'Already have an account?',
            'Connectez-vous' => 'Log in',
            'Agent comptable' => 'Accounting agent',
            'Ajouter un frais' => 'Add a fee',
            'Nom du frais' => 'Fee name',
            'Montant' => 'Amount',
            'Filiere' => 'Program',
            'Niveau' => 'Level',
            'Annee academique' => 'Academic year',
            'Date limite de paiement' => 'Payment deadline',
            'Description' => 'Description',
            'Enregistrer' => 'Save',
            'Retour' => 'Back',
            'Selectionner une filiere' => 'Select a program',
            'Selectionner un niveau' => 'Select a level',
            'Inscription' => 'Registration',
            'Transport' => 'Transport',
            'Hebergement' => 'Housing',
            'Tableau de bord agent' => 'Agent dashboard',
            'Vue financiere et paiements en attente.' => 'Financial overview and pending payments.',
            'Ajouter un etudiant' => 'Add a student',
            'Total etudiants' => 'Total students',
            'Etudiants inscrits' => 'Registered students',
            'Non inscrits' => 'Not registered',
            'Total factures' => 'Total invoices',
            'Montant attendu' => 'Expected amount',
            'Montant paye' => 'Paid amount',
            'Montant impaye' => 'Unpaid amount',
            'Paiements en attente' => 'Pending payments',
            'Factures en retard' => 'Late invoices',
            'Tout voir' => 'View all',
            'Reference' => 'Reference',
            'Facture' => 'Invoice',
            'Statut' => 'Status',
            'Examiner' => 'Review',
            'Aucun paiement en attente.' => 'No pending payments.',
            'Tableau de bord etudiant' => 'Student dashboard',
            'Payer une facture' => 'Pay an invoice',
            'Total facture' => 'Total billed',
            'Total' => 'Total',
            'Total paye' => 'Total paid',
            'Solde restant' => 'Remaining balance',
            'Frais disponibles' => 'Available fees',
            'Frais correspondant a votre filiere, niveau et annee academique.' => 'Fees matching your program, level, and academic year.',
            'Type' => 'Type',
            'Date limite' => 'Deadline',
            'Echeance' => 'Due date',
            'Payer' => 'Pay',
            'Paye' => 'Paid',
            'Reste' => 'Remaining',
            'En attente' => 'Pending',
            "Payez l'inscription d'abord" => 'Pay registration first',
            'Payez l inscription d abord' => 'Pay registration first',
            'Aucun frais disponible pour votre dossier.' => 'No fee is available for your record.',
            'Voir' => 'View',
            'Aucune facture disponible.' => 'No invoice available.',
            'Paiements recents' => 'Recent payments',
            'Recu' => 'Receipt',
            'Aucun paiement recent.' => 'No recent payment.',
            'Payee' => 'Paid',
            'PayÃ©e' => 'Paid',
            'Non payee' => 'Unpaid',
            'Non payÃ©e' => 'Unpaid',
            'Partiellement payee' => 'Partially paid',
            'Partiellement payÃ©e' => 'Partially paid',
            'En retard' => 'Late',
            'Valide' => 'Validated',
            'ValidÃ©' => 'Validated',
            'Rejete' => 'Rejected',
            'RejetÃ©' => 'Rejected',
            'Inscrit' => 'Registered',
            'Non inscrit' => 'Not registered',
            'Obligatoire' => 'Required',
            'Optionnel' => 'Optional',
            'A payer' => 'To pay',
            'Disponible' => 'Available',
            'Inscription requise' => 'Registration required',
            'Date limite depassee' => 'Deadline passed',
            'Bourse versee' => 'Stipend paid',
            'Aucune bourse versee.' => 'No stipend paid.',
            'Attribuee' => 'Assigned',
            'Non attribuee' => 'Not assigned',
            'Confirmer le mot de passe' => 'Confirm password',
            'Votre dossier financier doit deja exister.' => 'Your financial record must already exist.',
            'Creer le compte' => 'Create account',
            'Etape 2' => 'Step 2',
            'Inscription etudiant' => 'Student registration',
            'Inscription agent' => 'Agent registration',
            "Renseignez les informations de l'agent comptable." => 'Enter the accounting agent information.',
            'Attribution' => 'Assignment',
            'Ce mois' => 'This month',
            'Montant du mois' => 'Monthly amount',
            'Total recu' => 'Total received',
            'Montant eligible' => 'Eligible amount',
            'Mois courant' => 'Current month',
            'Prochaine bourse' => 'Next stipend',
            'En attente d attribution' => 'Waiting for assignment',
            'Historique des bourses' => 'Stipend history',
            'Mois' => 'Month',
            'Date de versement' => 'Disbursement date',
            'Attribution et suivi des bourses mensuelles.' => 'Monthly stipend assignment and tracking.',
            'Attribuees' => 'Assigned',
            'Non attribuees' => 'Not assigned',
            'A verser ce mois' => 'To disburse this month',
            'Total deja verse' => 'Total already disbursed',
            'Recherche' => 'Search',
            'Tous' => 'All',
            'Rechercher' => 'Search',
            'Effacer' => 'Clear',
            'Deja verse' => 'Already disbursed',
            'Montant mensuel' => 'Monthly amount',
            'Supprimer la bourse' => 'Remove stipend',
            'Attribuer la bourse' => 'Assign stipend',
            'Aucun etudiant trouve.' => 'No student found.',
            'Bourse attribuee.' => 'Stipend assigned.',
            'Bourse supprimee pour les prochains versements.' => 'Stipend removed for future disbursements.',
            'Le matricule est obligatoire.' => 'ID number is required.',
            'Le mot de passe est obligatoire.' => 'Password is required.',
            'Matricule ou mot de passe incorrect.' => 'Incorrect ID number or password.',
            '202400000000 ou ADM001' => '202400000000 or ADM001',
            'ADM001 ou matricule agent' => 'ADM001 or agent ID',
            'Tous les champs sont obligatoires.' => 'All fields are required.',
            'Le matricule etudiant doit commencer par 20xx et contenir 12 chiffres.' => 'The student ID must start with 20xx and contain 12 digits.',
            'Le mot de passe doit contenir au moins 8 caracteres.' => 'The password must contain at least 8 characters.',
            'La confirmation du mot de passe ne correspond pas.' => 'The password confirmation does not match.',
            'Aucun dossier financier ne correspond a ce matricule.' => 'No financial record matches this ID number.',
            'Le nom ou le prenom ne correspond pas au dossier financier.' => 'The first or last name does not match the financial record.',
            'Ce compte etudiant est deja inscrit.' => 'This student account is already registered.',
            'Ce matricule agent existe deja.' => 'This agent ID already exists.',
            'Inscription terminee. Vous pouvez vous connecter.' => 'Registration complete. You can log in.',
            'Compte agent cree. Vous pouvez vous connecter.' => 'Agent account created. You can log in.',
            'Choisissez un role.' => 'Choose a role.',
            'Session agent fermee pour continuer l inscription etudiant.' => 'Agent session closed to continue student registration.',
            'Dossiers financiers crees par les agents comptables.' => 'Financial records created by accounting agents.',
            'Matricule, nom, filiere, niveau' => 'ID number, name, program, level',
            'Ajouter un dossier financier etudiant' => 'Add a student financial record',
            'Creer le dossier' => 'Create record',
            'Modifier' => 'Edit',
            'Supprimer' => 'Delete',
            'Supprimer ce dossier financier ?' => 'Delete this financial record?',
            'Dossier financier supprime avec ses factures, paiements et recus.' => 'Financial record deleted with its invoices, payments, and receipts.',
            'Suppression impossible' => 'Deletion impossible',
            'Etudiant invalide.' => 'Invalid student.',
            'Etudiant introuvable.' => 'Student not found.',
            'Frais invalide.' => 'Invalid fee.',
            'Frais introuvable.' => 'Fee not found.',
            'Frais supprime avec les lignes de facture et paiements associes.' => 'Fee deleted with related invoice items and payments.',
            'Supprimer ce frais ?' => 'Delete this fee?',
            'Generer une facture' => 'Generate an invoice',
            'Generer manuellement' => 'Generate manually',
            'Generer la facture' => 'Generate invoice',
            'Generer le rapport' => 'Generate report',
            'Selectionnez un etudiant valide.' => 'Select a valid student.',
            'Selectionner un etudiant' => 'Select a student',
            'Charger les frais' => 'Load fees',
            'Date d echeance' => 'Due date',
            'La date d echeance est obligatoire.' => 'Due date is required.',
            'Un frais d inscription doit exister pour cette filiere, ce niveau et cette annee.' => 'A registration fee must exist for this program, level, and year.',
            'Aucun frais de facture selectionne.' => 'No invoice fee selected.',
            'Facture generee.' => 'Invoice generated.',
            'La facture n a pas pu etre generee' => 'The invoice could not be generated',
            'Factures generees automatiquement et visibles par les etudiants.' => 'Invoices generated automatically and visible to students.',
            'Filtrer' => 'Filter',
            'Toutes' => 'All',
            'Statut facture' => 'Invoice status',
            'Statut paiement' => 'Payment status',
            'Date debut' => 'Start date',
            'Date fin' => 'End date',
            'Total paiements' => 'Total payments',
            'Filtrer les factures, paiements et statuts d inscription.' => 'Filter invoices, payments, and registration statuses.',
            'Valider ou rejeter les paiements soumis par les etudiants.' => 'Validate or reject payments submitted by students.',
            'Valider le paiement' => 'Validate payment',
            'Examiner le paiement' => 'Review payment',
            'Valider' => 'Validate',
            'Rejeter' => 'Reject',
            'Motif de rejet' => 'Rejection reason',
            'Le motif de rejet est obligatoire.' => 'Rejection reason is required.',
            'Paiement introuvable.' => 'Payment not found.',
            'Paiement valide et recu genere.' => 'Payment validated and receipt generated.',
            'Paiement rejete.' => 'Payment rejected.',
            'Seuls les paiements en attente peuvent etre examines.' => 'Only pending payments can be reviewed.',
            'Le paiement n est plus en attente.' => 'The payment is no longer pending.',
            'Le montant depasse le reste de la cible.' => 'The amount exceeds the target remainder.',
            'Action invalide.' => 'Invalid action.',
            'Soumis le' => 'Submitted on',
            'Elements' => 'Items',
            'Elements de facture' => 'Invoice items',
            'Paye valide' => 'Validated paid',
            'Reste disponible' => 'Available remainder',
            'Cible' => 'Target',
            'Imprimer' => 'Print',
            'Voir facture' => 'View invoice',
            'Voir etudiant' => 'View student',
            'Informations etudiant' => 'Student information',
            'Cree le' => 'Created on',
            'Modifie le' => 'Updated on',
            'Recus' => 'Receipts',
            'Les recus existent seulement pour les paiements valides.' => 'Receipts exist only for validated payments.',
            'Emis le' => 'Issued on',
            'Nom etudiant' => 'Student name',
            'Numero de facture' => 'Invoice number',
            'Methode de paiement' => 'Payment method',
            'Reference de paiement' => 'Payment reference',
            'Date de paiement' => 'Payment date',
            'Date de validation' => 'Validation date',
            'Aucun recu trouve.' => 'No receipt found.',
            'Historique des paiements' => 'Payment history',
            'Methode' => 'Method',
            'Prenom' => 'First name',
            'Nom' => 'Last name',
            'Telephone' => 'Phone',
            'Methode' => 'Method',
            'informatique' => 'Computer science',
            'mathematique' => 'Mathematics',
            'ST' => 'Science and technology',
            'Biologie' => 'Biology',
            'Licence1' => 'Licence 1',
            'licence 2' => 'Licence 2',
            'licence3' => 'Licence 3',
            'master1' => 'Master 1',
            'master2' => 'Master 2',
            'L inscription est obligatoire. Transport et hebergement sont optionnels.' => 'Registration is mandatory. Transport and housing are optional.',
            'Carte Edahabia' => 'Edahabia card',
            'Virement bancaire' => 'Bank transfer',
            'Depot especes' => 'Cash deposit',
            'En ligne' => 'Online',
            'Cheque' => 'Check',
            'Nouveau paiement' => 'New payment',
            'Paiement valide' => 'Payment validated',
            'Paiement rejete' => 'Payment rejected',
        ],
        'ar' => [
            'Francais' => 'الفرنسية',
            'Langue' => 'اللغة',
            'Nuit' => 'ليلي',
            'Jour' => 'نهاري',
            'Activer le mode nuit' => 'تفعيل الوضع الليلي',
            'Activer le mode jour' => 'تفعيل الوضع النهاري',
            'Mode nuit' => 'الوضع الليلي',
            'Mode jour' => 'الوضع النهاري',
            'Notifications' => 'الإشعارات',
            'Tout lu' => 'تحديد الكل كمقروء',
            'Chargement...' => 'جار التحميل...',
            'Aucune notification.' => 'لا توجد إشعارات.',
            'Deconnexion' => 'تسجيل الخروج',
            'EduPay+ gestion comptable des etudiants' => 'EduPay+ لتسيير محاسبة الطلبة',
            'Tableau de bord' => 'لوحة التحكم',
            'Etudiants' => 'الطلبة',
            'Frais' => 'الرسوم',
            'Factures' => 'الفواتير',
            'Paiements' => 'المدفوعات',
            'Recus' => 'الوصولات',
            'Rapports' => 'التقارير',
            'Bourse' => 'المنحة',
            'Bourses' => 'المنح',
            'Profil' => 'الملف الشخصي',
            'Agent' => 'عون',
            'Etudiant' => 'طالب',
            'Accueil' => 'الرئيسية',
            'Bienvenue' => 'مرحبا',
            'La plateforme de gestion comptable dediee aux etudiants et agents.' => 'منصة تسيير محاسبي مخصصة للطلبة والأعوان.',
            'Connexion' => 'تسجيل الدخول',
            'Inscription' => 'التسجيل',
            'Gestion comptable des etudiants' => 'تسيير محاسبة الطلبة',
            'Acces etudiants et agents comptables.' => 'دخول الطلبة والأعوان المحاسبين.',
            'Matricule' => 'رقم التسجيل',
            'Mot de passe' => 'كلمة المرور',
            'Se connecter' => 'دخول',
            'Pas encore de compte ?' => 'ليس لديك حساب؟',
            'Inscrivez-vous' => 'سجل الآن',
            'Deja un compte ?' => 'لديك حساب؟',
            'Connectez-vous' => 'سجل الدخول',
            'Agent comptable' => 'عون محاسب',
            'Ajouter un frais' => 'إضافة رسم',
            'Nom du frais' => 'اسم الرسم',
            'Montant' => 'المبلغ',
            'Filiere' => 'الشعبة',
            'Niveau' => 'المستوى',
            'Annee academique' => 'السنة الجامعية',
            'Date limite de paiement' => 'آخر أجل للدفع',
            'Description' => 'الوصف',
            'Enregistrer' => 'حفظ',
            'Retour' => 'رجوع',
            'Selectionner une filiere' => 'اختر شعبة',
            'Selectionner un niveau' => 'اختر مستوى',
            'Transport' => 'النقل',
            'Hebergement' => 'الإيواء',
            'Tableau de bord agent' => 'لوحة تحكم العون',
            'Vue financiere et paiements en attente.' => 'نظرة مالية والمدفوعات المعلقة.',
            'Ajouter un etudiant' => 'إضافة طالب',
            'Total etudiants' => 'إجمالي الطلبة',
            'Etudiants inscrits' => 'الطلبة المسجلون',
            'Non inscrits' => 'غير المسجلين',
            'Total factures' => 'إجمالي الفواتير',
            'Montant attendu' => 'المبلغ المتوقع',
            'Montant paye' => 'المبلغ المدفوع',
            'Montant impaye' => 'المبلغ غير المدفوع',
            'Paiements en attente' => 'مدفوعات معلقة',
            'Factures en retard' => 'فواتير متأخرة',
            'Tout voir' => 'عرض الكل',
            'Reference' => 'المرجع',
            'Facture' => 'الفاتورة',
            'Statut' => 'الحالة',
            'Examiner' => 'مراجعة',
            'Aucun paiement en attente.' => 'لا توجد مدفوعات معلقة.',
            'Tableau de bord etudiant' => 'لوحة تحكم الطالب',
            'Payer une facture' => 'دفع فاتورة',
            'Total facture' => 'إجمالي الفواتير',
            'Total' => 'الإجمالي',
            'Total paye' => 'إجمالي المدفوع',
            'Solde restant' => 'الرصيد المتبقي',
            'Frais disponibles' => 'الرسوم المتاحة',
            'Frais correspondant a votre filiere, niveau et annee academique.' => 'الرسوم المطابقة لشعبتك ومستواك وسنتك الجامعية.',
            'Type' => 'النوع',
            'Date limite' => 'آخر أجل',
            'Echeance' => 'تاريخ الاستحقاق',
            'Payer' => 'دفع',
            'Paye' => 'مدفوع',
            'Reste' => 'الباقي',
            'En attente' => 'معلق',
            "Payez l'inscription d'abord" => 'ادفع رسوم التسجيل أولا',
            'Payez l inscription d abord' => 'ادفع رسوم التسجيل أولا',
            'Aucun frais disponible pour votre dossier.' => 'لا توجد رسوم متاحة لملفك.',
            'Voir' => 'عرض',
            'Aucune facture disponible.' => 'لا توجد فواتير.',
            'Paiements recents' => 'المدفوعات الأخيرة',
            'Recu' => 'وصل',
            'Aucun paiement recent.' => 'لا توجد مدفوعات حديثة.',
            'Payee' => 'مدفوعة',
            'PayÃ©e' => 'مدفوعة',
            'Non payee' => 'غير مدفوعة',
            'Non payÃ©e' => 'غير مدفوعة',
            'Partiellement payee' => 'مدفوعة جزئيا',
            'Partiellement payÃ©e' => 'مدفوعة جزئيا',
            'En retard' => 'متأخرة',
            'Valide' => 'تم التحقق',
            'ValidÃ©' => 'تم التحقق',
            'Rejete' => 'مرفوض',
            'RejetÃ©' => 'مرفوض',
            'Inscrit' => 'مسجل',
            'Non inscrit' => 'غير مسجل',
            'Obligatoire' => 'إجباري',
            'Optionnel' => 'اختياري',
            'A payer' => 'للدفع',
            'Disponible' => 'متاح',
            'Inscription requise' => 'التسجيل مطلوب',
            'Date limite depassee' => 'انتهى الأجل',
            'Bourse versee' => 'تم صرف المنحة',
            'Aucune bourse versee.' => 'لا توجد منحة مصروفة.',
            'Attribuee' => 'ممنوحة',
            'Non attribuee' => 'غير ممنوحة',
            'Confirmer le mot de passe' => 'تأكيد كلمة المرور',
            'Votre dossier financier doit deja exister.' => 'يجب أن يكون ملفك المالي موجودا مسبقا.',
            'Creer le compte' => 'إنشاء الحساب',
            'Etape 2' => 'المرحلة 2',
            'Inscription etudiant' => 'تسجيل الطالب',
            'Inscription agent' => 'تسجيل العون',
            "Renseignez les informations de l'agent comptable." => 'أدخل معلومات العون المحاسب.',
            'Attribution' => 'الإسناد',
            'Ce mois' => 'هذا الشهر',
            'Montant du mois' => 'مبلغ الشهر',
            'Total recu' => 'إجمالي المستلم',
            'Montant eligible' => 'المبلغ المستحق',
            'Mois courant' => 'الشهر الحالي',
            'Prochaine bourse' => 'المنحة القادمة',
            'En attente d attribution' => 'في انتظار الإسناد',
            'Historique des bourses' => 'سجل المنح',
            'Mois' => 'الشهر',
            'Date de versement' => 'تاريخ الصرف',
            'Attribution et suivi des bourses mensuelles.' => 'إسناد ومتابعة المنح الشهرية.',
            'Attribuees' => 'ممنوحة',
            'Non attribuees' => 'غير ممنوحة',
            'A verser ce mois' => 'للصرف هذا الشهر',
            'Total deja verse' => 'إجمالي المصروف سابقا',
            'Recherche' => 'بحث',
            'Tous' => 'الكل',
            'Rechercher' => 'بحث',
            'Effacer' => 'مسح',
            'Deja verse' => 'مصروف سابقا',
            'Montant mensuel' => 'المبلغ الشهري',
            'Supprimer la bourse' => 'حذف المنحة',
            'Attribuer la bourse' => 'إسناد المنحة',
            'Aucun etudiant trouve.' => 'لم يتم العثور على أي طالب.',
            'Bourse attribuee.' => 'تم إسناد المنحة.',
            'Bourse supprimee pour les prochains versements.' => 'تم حذف المنحة للدفعات القادمة.',
            'Le matricule est obligatoire.' => 'رقم التسجيل إجباري.',
            'Le mot de passe est obligatoire.' => 'كلمة المرور إجبارية.',
            'Matricule ou mot de passe incorrect.' => 'رقم التسجيل أو كلمة المرور غير صحيحة.',
            '202400000000 ou ADM001' => '202400000000 أو ADM001',
            'ADM001 ou matricule agent' => 'ADM001 أو رقم تسجيل العون',
            'Tous les champs sont obligatoires.' => 'كل الحقول إجبارية.',
            'Le matricule etudiant doit commencer par 20xx et contenir 12 chiffres.' => 'رقم تسجيل الطالب يجب أن يبدأ بـ 20xx ويتكون من 12 رقما.',
            'Le mot de passe doit contenir au moins 8 caracteres.' => 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل.',
            'La confirmation du mot de passe ne correspond pas.' => 'تأكيد كلمة المرور غير مطابق.',
            'Aucun dossier financier ne correspond a ce matricule.' => 'لا يوجد ملف مالي مطابق لهذا الرقم.',
            'Le nom ou le prenom ne correspond pas au dossier financier.' => 'الاسم أو اللقب لا يطابق الملف المالي.',
            'Ce compte etudiant est deja inscrit.' => 'حساب الطالب هذا مسجل مسبقا.',
            'Ce matricule agent existe deja.' => 'رقم تسجيل العون هذا موجود مسبقا.',
            'Inscription terminee. Vous pouvez vous connecter.' => 'تم التسجيل. يمكنك تسجيل الدخول.',
            'Compte agent cree. Vous pouvez vous connecter.' => 'تم إنشاء حساب العون. يمكنك تسجيل الدخول.',
            'Choisissez un role.' => 'اختر دورا.',
            'Session agent fermee pour continuer l inscription etudiant.' => 'تم إغلاق جلسة العون لمتابعة تسجيل الطالب.',
            'Dossiers financiers crees par les agents comptables.' => 'ملفات مالية أنشأها الأعوان المحاسبون.',
            'Matricule, nom, filiere, niveau' => 'رقم التسجيل، الاسم، الشعبة، المستوى',
            'Ajouter un dossier financier etudiant' => 'إضافة ملف مالي لطالب',
            'Creer le dossier' => 'إنشاء الملف',
            'Modifier' => 'تعديل',
            'Supprimer' => 'حذف',
            'Supprimer ce dossier financier ?' => 'هل تريد حذف هذا الملف المالي؟',
            'Dossier financier supprime avec ses factures, paiements et recus.' => 'تم حذف الملف المالي مع فواتيره ومدفوعاته ووصولاته.',
            'Suppression impossible' => 'تعذر الحذف',
            'Etudiant invalide.' => 'طالب غير صالح.',
            'Etudiant introuvable.' => 'الطالب غير موجود.',
            'Frais invalide.' => 'رسم غير صالح.',
            'Frais introuvable.' => 'الرسم غير موجود.',
            'Frais supprime avec les lignes de facture et paiements associes.' => 'تم حذف الرسم مع عناصر الفاتورة والمدفوعات المرتبطة.',
            'Supprimer ce frais ?' => 'هل تريد حذف هذا الرسم؟',
            'Generer une facture' => 'إنشاء فاتورة',
            'Generer manuellement' => 'إنشاء يدويا',
            'Generer la facture' => 'إنشاء الفاتورة',
            'Generer le rapport' => 'إنشاء التقرير',
            'Selectionnez un etudiant valide.' => 'اختر طالبا صالحا.',
            'Selectionner un etudiant' => 'اختر طالبا',
            'Charger les frais' => 'تحميل الرسوم',
            'Date d echeance' => 'تاريخ الاستحقاق',
            'La date d echeance est obligatoire.' => 'تاريخ الاستحقاق إجباري.',
            'Un frais d inscription doit exister pour cette filiere, ce niveau et cette annee.' => 'يجب أن يوجد رسم تسجيل لهذه الشعبة وهذا المستوى وهذه السنة.',
            'Aucun frais de facture selectionne.' => 'لم يتم اختيار أي رسم للفاتورة.',
            'Facture generee.' => 'تم إنشاء الفاتورة.',
            'La facture n a pas pu etre generee' => 'تعذر إنشاء الفاتورة',
            'Factures generees automatiquement et visibles par les etudiants.' => 'فواتير تنشأ تلقائيا وتظهر للطلبة.',
            'Filtrer' => 'تصفية',
            'Toutes' => 'الكل',
            'Statut facture' => 'حالة الفاتورة',
            'Statut paiement' => 'حالة الدفع',
            'Date debut' => 'تاريخ البداية',
            'Date fin' => 'تاريخ النهاية',
            'Total paiements' => 'إجمالي المدفوعات',
            'Filtrer les factures, paiements et statuts d inscription.' => 'تصفية الفواتير والمدفوعات وحالات التسجيل.',
            'Valider ou rejeter les paiements soumis par les etudiants.' => 'قبول أو رفض المدفوعات المقدمة من الطلبة.',
            'Valider le paiement' => 'قبول الدفع',
            'Examiner le paiement' => 'مراجعة الدفع',
            'Valider' => 'قبول',
            'Rejeter' => 'رفض',
            'Motif de rejet' => 'سبب الرفض',
            'Le motif de rejet est obligatoire.' => 'سبب الرفض إجباري.',
            'Paiement introuvable.' => 'الدفع غير موجود.',
            'Paiement valide et recu genere.' => 'تم قبول الدفع وإنشاء الوصل.',
            'Paiement rejete.' => 'تم رفض الدفع.',
            'Seuls les paiements en attente peuvent etre examines.' => 'يمكن مراجعة المدفوعات المعلقة فقط.',
            'Le paiement n est plus en attente.' => 'الدفع لم يعد معلقا.',
            'Le montant depasse le reste de la cible.' => 'المبلغ يتجاوز الباقي المطلوب.',
            'Action invalide.' => 'إجراء غير صالح.',
            'Soumis le' => 'أرسل في',
            'Elements' => 'العناصر',
            'Elements de facture' => 'عناصر الفاتورة',
            'Paye valide' => 'مدفوع مقبول',
            'Reste disponible' => 'الباقي المتاح',
            'Cible' => 'الهدف',
            'Imprimer' => 'طباعة',
            'Voir facture' => 'عرض الفاتورة',
            'Voir etudiant' => 'عرض الطالب',
            'Informations etudiant' => 'معلومات الطالب',
            'Cree le' => 'أنشئ في',
            'Modifie le' => 'عدل في',
            'Les recus existent seulement pour les paiements valides.' => 'الوصولات توجد فقط للمدفوعات المقبولة.',
            'Emis le' => 'صدر في',
            'Nom etudiant' => 'اسم الطالب',
            'Numero de facture' => 'رقم الفاتورة',
            'Methode de paiement' => 'طريقة الدفع',
            'Reference de paiement' => 'مرجع الدفع',
            'Date de paiement' => 'تاريخ الدفع',
            'Date de validation' => 'تاريخ القبول',
            'Aucun recu trouve.' => 'لم يتم العثور على أي وصل.',
            'Historique des paiements' => 'سجل المدفوعات',
            'Prenom' => 'الاسم',
            'Nom' => 'اللقب',
            'Telephone' => 'الهاتف',
            'Methode' => 'الطريقة',
            'informatique' => 'الإعلام الآلي',
            'mathematique' => 'الرياضيات',
            'ST' => 'علوم وتكنولوجيا',
            'Biologie' => 'البيولوجيا',
            'Licence1' => 'ليسانس 1',
            'licence 2' => 'ليسانس 2',
            'licence3' => 'ليسانس 3',
            'master1' => 'ماستر 1',
            'master2' => 'ماستر 2',
            'L inscription est obligatoire. Transport et hebergement sont optionnels.' => 'التسجيل إجباري. النقل والإيواء اختياريان.',
            'Carte Edahabia' => 'بطاقة الذهبية',
            'Virement bancaire' => 'تحويل بنكي',
            'Depot especes' => 'إيداع نقدي',
            'En ligne' => 'عبر الإنترنت',
            'Cheque' => 'شيك',
            'Nouveau paiement' => 'دفع جديد',
            'Paiement valide' => 'تم قبول الدفع',
            'Paiement rejete' => 'تم رفض الدفع',
        ],
    ];

    return $catalog;
}

function app_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    foreach (['/admin/', '/student/', '/api/'] as $segment) {
        $position = strpos($script, $segment);
        if ($position !== false) {
            $base = rtrim(substr($script, 0, $position), '/');
            return $base;
        }
    }

    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    $base = $dir === '/' || $dir === '.' ? '' : $dir;
    return $base;
}

function url(string $path = ''): string
{
    return app_base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function query_all(string $sql, array $params = []): array
{
    // Run a prepared SELECT and return every row.
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function query_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function query_value(string $sql, array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function csrf_token(): string
{
    // Generate one session-bound token and reuse it for all forms in the session.
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_app_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Jeton de formulaire invalide.');
    }
}

function flash(string $type, string $message): void
{
    start_app_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function consume_flash(): array
{
    start_app_session();
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function current_admin_id(): ?int
{
    start_app_session();
    return isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
}

function current_student_id(): ?int
{
    start_app_session();
    return isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null;
}

function require_admin(): void
{
    // Redirect guests away from agent pages and clear conflicting student sessions.
    start_app_session();
    if (empty($_SESSION['admin_id'])) {
        redirect('login.php');
    }
    if (!empty($_SESSION['student_id'])) {
        clear_student_session();
    }
}

function require_student(): void
{
    // Redirect guests away from student pages and clear conflicting agent sessions.
    start_app_session();
    if (empty($_SESSION['student_id'])) {
        redirect('login.php');
    }
    if (!empty($_SESSION['admin_id'])) {
        clear_admin_session();
    }
}

function current_admin(): ?array
{
    $id = current_admin_id();
    return $id ? query_one('SELECT * FROM admins WHERE id = ?', [$id]) : null;
}

function current_student(): ?array
{
    $id = current_student_id();
    return $id ? query_one('SELECT * FROM students WHERE id = ?', [$id]) : null;
}

function clear_admin_session(): void
{
    start_app_session();
    unset($_SESSION['admin_id'], $_SESSION['admin_code'], $_SESSION['admin_matricule'], $_SESSION['admin_name'], $_SESSION['admin_role']);
}

function clear_student_session(): void
{
    start_app_session();
    unset($_SESSION['student_id'], $_SESSION['student_matricule'], $_SESSION['student_name']);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function money(float|int|string|null $amount): string
{
    return number_format((float)$amount, 2);
}

function badge(string $status): string
{
    // Convert internal statuses to consistent visible labels and CSS classes.
    $labels = [
        'paid' => tr('Payee'),
        'unpaid' => tr('Non payee'),
        'partially_paid' => tr('Partiellement payee'),
        'late' => tr('En retard'),
        'pending' => tr('En attente'),
        'validated' => tr('Valide'),
        'rejected' => tr('Rejete'),
        'registered' => tr('Inscrit'),
        'not_registered' => tr('Non inscrit'),
        'obligatory' => tr('Obligatoire'),
        'optional' => tr('Optionnel'),
        'payable' => tr('A payer'),
        'not_invoiced' => tr('Disponible'),
        'blocked_registration' => tr('Inscription requise'),
        'expired' => tr('Date limite depassee'),
        'disbursed' => tr('Bourse versee'),
        'assigned' => tr('Attribuee'),
        'not_assigned' => tr('Non attribuee'),
    ];
    $label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
    return '<span class="badge badge-' . h($status) . '">' . h($label) . '</span>';
}

function validate_student_code(string $studentCode): bool
{
    return preg_match('/^20[0-9]{10}$/', $studentCode) === 1;
}

function validate_student_matricule(string $matricule): bool
{
    return validate_student_code($matricule);
}

function validate_admin_code(string $adminCode): bool
{
    return preg_match('/^ADM[0-9]{3}$/', $adminCode) === 1;
}

function normalize_identity_value(string $value): string
{
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function student_identity_matches(array $student, string $firstName, string $lastName): bool
{
    return normalize_identity_value((string)$student['first_name']) === normalize_identity_value($firstName)
        && normalize_identity_value((string)$student['last_name']) === normalize_identity_value($lastName);
}

function generate_admin_code(): string
{
    for ($i = 1; $i <= 999; $i++) {
        $code = 'ADM' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
        if ((int)query_value('SELECT COUNT(*) FROM admins WHERE admin_code = ?', [$code]) === 0) {
            return $code;
        }
    }

    throw new RuntimeException('Aucun code agent disponible.');
}

function fee_type_for_name(string $feeName): ?string
{
    return match ($feeName) {
        'registration' => 'obligatory',
        'transport', 'housing' => 'optional',
        default => null,
    };
}

function fee_label(?string $feeName): string
{
    return match ($feeName) {
        'registration' => tr('Inscription'),
        'transport' => tr('Transport'),
        'housing' => tr('Hebergement'),
        null, '' => tr('Facture'),
        default => ucwords(str_replace('_', ' ', $feeName)),
    };
}

function program_options(bool $includeAll = false): array
{
    $options = [
        'informatique' => tr('informatique'),
        'mathematique' => tr('mathematique'),
        'ST' => tr('ST'),
        'Biologie' => tr('Biologie'),
    ];

    return $includeAll ? ['ALL' => tr('Tous')] + $options : $options;
}

function level_options(bool $includeAll = false): array
{
    $options = [
        'Licence1' => tr('Licence1'),
        'licence 2' => tr('licence 2'),
        'licence3' => tr('licence3'),
        'master1' => tr('master1'),
        'master2' => tr('master2'),
    ];

    return $includeAll ? ['ALL' => tr('Tous')] + $options : $options;
}

function program_label(?string $program): string
{
    if ($program === null || $program === '') {
        return '';
    }

    return $program === 'ALL' ? tr('Tous') : tr($program);
}

function level_label(?string $level): string
{
    if ($level === null || $level === '') {
        return '';
    }

    return $level === 'ALL' ? tr('Tous') : tr($level);
}

function is_valid_program(string $program, bool $includeAll = false): bool
{
    return array_key_exists($program, program_options($includeAll));
}

function is_valid_level(string $level, bool $includeAll = false): bool
{
    return array_key_exists($level, level_options($includeAll));
}

function field_label(string $field): string
{
    return [
        'matricule' => tr('Matricule'),
        'first_name' => tr('Prenom'),
        'last_name' => tr('Nom'),
        'program' => tr('Filiere'),
        'level' => tr('Niveau'),
        'academic_year' => tr('Annee academique'),
        'phone' => tr('Telephone'),
        'amount' => tr('Montant'),
    ][$field] ?? $field;
}

function stipend_level_group(?string $level): ?string
{
    // Normalize labels like "licence 2" and "master1" into stipend categories.
    $normalized = strtolower(preg_replace('/\s+/', '', (string)$level));
    if (str_starts_with($normalized, 'licence')) {
        return 'licence';
    }
    if (str_starts_with($normalized, 'master')) {
        return 'master';
    }

    return null;
}

function stipend_amount_for_level(?string $level): float
{
    return match (stipend_level_group($level)) {
        'licence' => 2000.0,
        'master' => 5000.0,
        default => 0.0,
    };
}

function current_stipend_month(): string
{
    return date('Y-m-01');
}

function next_stipend_date(?string $fromMonth = null): string
{
    return date('Y-m-01', strtotime(($fromMonth ?: current_stipend_month()) . ' +1 month'));
}

function ensure_monthly_stipend_for_student(int $studentId, ?string $month = null): void
{
    // Idempotently create this month's stipend only when an agent assigned eligibility.
    $month = $month ?: current_stipend_month();
    $student = query_one('SELECT id, level, stipend_enabled FROM students WHERE id = ?', [$studentId]);
    if (!$student) {
        return;
    }
    if ((int)($student['stipend_enabled'] ?? 0) !== 1) {
        return;
    }

    $levelGroup = stipend_level_group($student['level']);
    $amount = stipend_amount_for_level($student['level']);
    if ($levelGroup === null || $amount <= 0) {
        return;
    }

    try {
        $stmt = db()->prepare(
            'INSERT IGNORE INTO student_stipends (student_id, stipend_month, amount, level_group)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$studentId, $month, $amount, $levelGroup]);
    } catch (PDOException $exception) {
        if ((string)$exception->getCode() !== '42S02' && !str_contains($exception->getMessage(), '1932')) {
            throw $exception;
        }
        if (function_exists('ensure_stipends_table_usable')) {
            ensure_stipends_table_usable(db());
            $stmt = db()->prepare(
                'INSERT IGNORE INTO student_stipends (student_id, stipend_month, amount, level_group)
                 VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$studentId, $month, $amount, $levelGroup]);
        }
    }
}

function ensure_monthly_stipends_for_all_students(?string $month = null): void
{
    foreach (query_all('SELECT id FROM students') as $student) {
        ensure_monthly_stipend_for_student((int)$student['id'], $month);
    }
}

function student_stipend_summary(int $studentId): array
{
    // Build the student-facing bourse status cards and recent history.
    ensure_monthly_stipend_for_student($studentId);
    $currentMonth = current_stipend_month();
    $current = query_one(
        'SELECT id, amount, stipend_month, disbursed_at
         FROM student_stipends
         WHERE student_id = ? AND stipend_month = ?
         LIMIT 1',
        [$studentId, $currentMonth]
    );

    $history = query_all(
        'SELECT id, stipend_month, amount, level_group, status, disbursed_at
         FROM student_stipends
         WHERE student_id = ?
         ORDER BY stipend_month DESC, id DESC
         LIMIT 12',
        [$studentId]
    );

    return [
        'eligible_amount' => stipend_amount_for_level(query_value('SELECT level FROM students WHERE id = ?', [$studentId])),
        'is_enabled' => (int)query_value('SELECT stipend_enabled FROM students WHERE id = ?', [$studentId]) === 1,
        'current_month' => $currentMonth,
        'current_month_received' => $current !== null,
        'current_month_amount' => $current ? (float)$current['amount'] : 0.0,
        'current_month_disbursed_at' => $current['disbursed_at'] ?? null,
        'next_stipend_date' => next_stipend_date($currentMonth),
        'total_received' => (float)query_value('SELECT COALESCE(SUM(amount), 0) FROM student_stipends WHERE student_id = ?', [$studentId]),
        'disbursement_count' => (int)query_value('SELECT COUNT(*) FROM student_stipends WHERE student_id = ?', [$studentId]),
        'history' => $history,
    ];
}

function payment_method_label(?string $method): string
{
    return [
        'carte_edahabia' => tr('Carte Edahabia'),
        'eccp' => 'ECCP',
        'bank_transfer' => tr('Virement bancaire'),
        'cash_deposit' => tr('Depot especes'),
        'online' => tr('En ligne'),
        'cheque' => tr('Cheque'),
    ][$method ?? ''] ?? (string)$method;
}

function invoice_status(float $paid, float $remaining, ?string $dueDate): string
{
    if ($remaining <= 0.009) {
        return 'paid';
    }

    if ($dueDate && $dueDate < date('Y-m-d')) {
        return 'late';
    }

    if ($paid > 0.009 && $remaining > 0.009) {
        return 'partially_paid';
    }

    return 'unpaid';
}

function fee_is_expired(array $fee): bool
{
    return !empty($fee['due_date']) && $fee['due_date'] < date('Y-m-d');
}

function recalculate_invoice_totals(int $invoiceId): void
{
    // Recompute invoice totals from items and validated payments after payment changes.
    $invoice = query_one('SELECT id, due_date FROM invoices WHERE id = ?', [$invoiceId]);
    if (!$invoice) {
        return;
    }

    $total = (float)query_value('SELECT COALESCE(SUM(amount), 0) FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
    $paid = (float)query_value(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'validated'",
        [$invoiceId]
    );
    $paid = min($paid, $total);
    $remaining = max(0, $total - $paid);
    $status = invoice_status($paid, $remaining, $invoice['due_date'] ?? null);

    $stmt = db()->prepare('UPDATE invoices SET total_amount = ?, paid_amount = ?, remaining_amount = ?, status = ? WHERE id = ?');
    $stmt->execute([$total, $paid, $remaining, $status, $invoiceId]);
}

function hard_delete_student(int $studentId): void
{
    // Remove a student and all dependent accounting records inside one transaction.
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $exists = (int)query_value('SELECT COUNT(*) FROM students WHERE id = ?', [$studentId]);
        if ($exists === 0) {
            throw new RuntimeException('Etudiant introuvable.');
        }

        $stmt = $pdo->prepare(
            'DELETE FROM receipts
             WHERE student_id = ?
                OR invoice_id IN (SELECT id FROM invoices WHERE student_id = ?)
                OR payment_id IN (
                    SELECT id FROM payments
                    WHERE student_id = ? OR invoice_id IN (SELECT id FROM invoices WHERE student_id = ?)
                )'
        );
        $stmt->execute([$studentId, $studentId, $studentId, $studentId]);

        $stmt = $pdo->prepare(
            'DELETE FROM payments
             WHERE student_id = ? OR invoice_id IN (SELECT id FROM invoices WHERE student_id = ?)'
        );
        $stmt->execute([$studentId, $studentId]);

        $stmt = $pdo->prepare(
            'DELETE ii
             FROM invoice_items ii
             INNER JOIN invoices i ON i.id = ii.invoice_id
             WHERE i.student_id = ?'
        );
        $stmt->execute([$studentId]);

        $stmt = $pdo->prepare('DELETE FROM invoices WHERE student_id = ?');
        $stmt->execute([$studentId]);

        $stmt = $pdo->prepare('DELETE FROM student_stipends WHERE student_id = ?');
        $stmt->execute([$studentId]);

        $stmt = $pdo->prepare('DELETE FROM students WHERE id = ?');
        $stmt->execute([$studentId]);

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function hard_delete_fee(int $feeId): void
{
    // Remove a fee and all generated invoice/payment artifacts linked to it.
    $pdo = db();
    $invoiceIds = array_map(
        static fn(array $row): int => (int)$row['invoice_id'],
        query_all('SELECT DISTINCT invoice_id FROM invoice_items WHERE fee_id = ?', [$feeId])
    );

    $pdo->beginTransaction();

    try {
        $exists = (int)query_value('SELECT COUNT(*) FROM fees WHERE id = ?', [$feeId]);
        if ($exists === 0) {
            throw new RuntimeException('Frais introuvable.');
        }

        $stmt = $pdo->prepare(
            'DELETE r
             FROM receipts r
             INNER JOIN payments p ON p.id = r.payment_id
             INNER JOIN invoice_items ii ON ii.id = p.invoice_item_id
             WHERE ii.fee_id = ?'
        );
        $stmt->execute([$feeId]);

        $stmt = $pdo->prepare(
            'DELETE p
             FROM payments p
             INNER JOIN invoice_items ii ON ii.id = p.invoice_item_id
             WHERE ii.fee_id = ?'
        );
        $stmt->execute([$feeId]);

        $stmt = $pdo->prepare('DELETE FROM invoice_items WHERE fee_id = ?');
        $stmt->execute([$feeId]);

        $stmt = $pdo->prepare('DELETE FROM fees WHERE id = ?');
        $stmt->execute([$feeId]);

        foreach ($invoiceIds as $invoiceId) {
            $itemCount = (int)query_value('SELECT COUNT(*) FROM invoice_items WHERE invoice_id = ?', [$invoiceId]);
            if ($itemCount === 0) {
                $stmt = $pdo->prepare('DELETE FROM receipts WHERE invoice_id = ?');
                $stmt->execute([$invoiceId]);

                $stmt = $pdo->prepare(
                    'DELETE r
                     FROM receipts r
                     INNER JOIN payments p ON p.id = r.payment_id
                     WHERE p.invoice_id = ?'
                );
                $stmt->execute([$invoiceId]);

                $stmt = $pdo->prepare('DELETE FROM payments WHERE invoice_id = ?');
                $stmt->execute([$invoiceId]);

                $stmt = $pdo->prepare('DELETE FROM invoices WHERE id = ?');
                $stmt->execute([$invoiceId]);
            } else {
                recalculate_invoice_totals($invoiceId);
            }
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function sync_invoice_status(int $invoiceId): void
{
    $invoice = query_one('SELECT id, total_amount, due_date FROM invoices WHERE id = ?', [$invoiceId]);
    if (!$invoice) {
        return;
    }

    $paid = (float)query_value(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = ? AND status = 'validated'",
        [$invoiceId]
    );
    $total = (float)$invoice['total_amount'];
    $remaining = max(0, $total - $paid);
    $status = invoice_status($paid, $remaining, $invoice['due_date'] ?? null);

    $stmt = db()->prepare('UPDATE invoices SET paid_amount = ?, remaining_amount = ?, status = ? WHERE id = ?');
    $stmt->execute([$paid, $remaining, $status, $invoiceId]);
}

function sync_all_invoice_statuses(): void
{
    $ids = query_all('SELECT id FROM invoices');
    foreach ($ids as $row) {
        sync_invoice_status((int)$row['id']);
    }
}

function generate_invoice_number(): string
{
    do {
        $number = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    } while (query_value('SELECT COUNT(*) FROM invoices WHERE invoice_number = ?', [$number]) > 0);

    return $number;
}

function generate_receipt_number(): string
{
    do {
        $number = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    } while (query_value('SELECT COUNT(*) FROM receipts WHERE receipt_number = ?', [$number]) > 0);

    return $number;
}

function generate_payment_reference(): string
{
    do {
        $reference = 'PAY-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    } while (query_value('SELECT COUNT(*) FROM payments WHERE payment_reference = ?', [$reference]) > 0);

    return $reference;
}

function invoice_item_paid(int $invoiceItemId, array $statuses = ['validated']): float
{
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $params = array_merge([$invoiceItemId], $statuses);
    return (float)query_value(
        "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_item_id = ? AND status IN ({$placeholders})",
        $params
    );
}

function invoice_item_remaining(int $invoiceItemId, bool $includePending = true): float
{
    $item = query_one('SELECT amount FROM invoice_items WHERE id = ?', [$invoiceItemId]);
    if (!$item) {
        return 0.0;
    }

    $statuses = $includePending ? ['pending', 'validated'] : ['validated'];
    return max(0, (float)$item['amount'] - invoice_item_paid($invoiceItemId, $statuses));
}

function invoice_item_has_pending_payment(int $invoiceItemId): bool
{
    return (int)query_value(
        "SELECT COUNT(*) FROM payments WHERE invoice_item_id = ? AND status = 'pending'",
        [$invoiceItemId]
    ) > 0;
}

function registration_item_for_invoice(int $invoiceId): ?array
{
    return query_one(
        "SELECT * FROM invoice_items WHERE invoice_id = ? AND fee_name = 'registration' ORDER BY id ASC LIMIT 1",
        [$invoiceId]
    );
}

function registration_is_paid(int $invoiceId): bool
{
    $registration = registration_item_for_invoice($invoiceId);
    if (!$registration) {
        return true;
    }

    return invoice_item_remaining((int)$registration['id'], false) <= 0.009;
}

function student_registration_paid_for_scope(int $studentId, string $program, string $level, string $academicYear): bool
{
    $items = query_all(
        "SELECT ii.id
         FROM invoice_items ii
         JOIN invoices i ON i.id = ii.invoice_id
         JOIN fees f ON f.id = ii.fee_id
         JOIN students s ON s.id = i.student_id
         WHERE i.student_id = ?
           AND ii.fee_name = 'registration'
           AND (f.program = s.program OR f.program = 'ALL')
           AND (f.level = s.level OR f.level = 'ALL')
           AND f.academic_year = ?
           AND (? = 'ALL' OR f.program = ? OR f.program = 'ALL')
           AND (? = 'ALL' OR f.level = ? OR f.level = 'ALL')",
        [$studentId, $academicYear, $program, $program, $level, $level]
    );

    foreach ($items as $item) {
        if (invoice_item_remaining((int)$item['id'], false) <= 0.009) {
            return true;
        }
    }

    return false;
}

function student_owns_invoice(int $studentId, int $invoiceId): bool
{
    return (int)query_value('SELECT COUNT(*) FROM invoices WHERE id = ? AND student_id = ?', [$invoiceId, $studentId]) === 1;
}

function student_fee_payment_summary(int $studentId, array $fee): array
{
    if (fee_is_expired($fee)) {
        return [
            'status' => 'expired',
            'can_pay' => false,
            'remaining_amount' => 0.0,
            'invoice_id' => null,
            'invoice_item_id' => null,
        ];
    }

    $items = query_all(
        "SELECT ii.id AS invoice_item_id, ii.amount, i.id AS invoice_id, i.invoice_number
         FROM invoice_items ii
         JOIN invoices i ON i.id = ii.invoice_id
         WHERE i.student_id = ? AND ii.fee_id = ?
         ORDER BY i.created_at DESC, ii.id DESC",
        [$studentId, $fee['id']]
    );

    foreach ($items as $item) {
        if (invoice_item_has_pending_payment((int)$item['invoice_item_id'])) {
            return [
                'status' => 'pending',
                'can_pay' => false,
                'remaining_amount' => 0.0,
                'invoice_id' => null,
                'invoice_item_id' => null,
            ];
        }

        $remainingValidatedOnly = invoice_item_remaining((int)$item['invoice_item_id'], false);

        if ($remainingValidatedOnly > 0.009) {
            if (
                $fee['fee_name'] !== 'registration'
                && !student_registration_paid_for_scope($studentId, $fee['program'], $fee['level'], $fee['academic_year'])
            ) {
                return [
                    'status' => 'blocked_registration',
                    'can_pay' => false,
                    'remaining_amount' => $remainingValidatedOnly,
                    'invoice_id' => null,
                    'invoice_item_id' => null,
                ];
            }

            return [
                'status' => 'payable',
                'can_pay' => true,
                'remaining_amount' => $remainingValidatedOnly,
                'invoice_id' => (int)$item['invoice_id'],
                'invoice_item_id' => (int)$item['invoice_item_id'],
            ];
        }
    }

    if ($items) {
        return [
            'status' => 'paid',
            'can_pay' => false,
            'remaining_amount' => 0.0,
            'invoice_id' => null,
            'invoice_item_id' => null,
        ];
    }

    if (
        $fee['fee_name'] !== 'registration'
        && !student_registration_paid_for_scope($studentId, $fee['program'], $fee['level'], $fee['academic_year'])
    ) {
        return [
            'status' => 'blocked_registration',
            'can_pay' => false,
            'remaining_amount' => (float)$fee['amount'],
            'invoice_id' => null,
            'invoice_item_id' => null,
        ];
    }

    return [
        'status' => 'not_invoiced',
        'can_pay' => true,
        'remaining_amount' => (float)$fee['amount'],
        'invoice_id' => null,
        'invoice_item_id' => null,
    ];
}

function student_fee_for_payment(int $studentId, int $feeId): ?array
{
    return query_one(
        "SELECT f.*
         FROM fees f
         JOIN students s ON (f.program = s.program OR f.program = 'ALL')
                        AND (f.level = s.level OR f.level = 'ALL')
                        AND s.academic_year = f.academic_year
         WHERE s.id = ? AND f.id = ?
           AND (f.due_date IS NULL OR f.due_date >= CURDATE())",
        [$studentId, $feeId]
    );
}

function existing_student_fee_invoice_target(int $studentId, int $feeId): ?array
{
    $target = query_one(
        "SELECT i.id AS invoice_id, ii.id AS invoice_item_id
         FROM invoice_items ii
         JOIN invoices i ON i.id = ii.invoice_id
         WHERE i.student_id = ? AND ii.fee_id = ?
         ORDER BY i.created_at DESC, ii.id DESC
         LIMIT 1",
        [$studentId, $feeId]
    );

    return $target ? [
        'invoice_id' => (int)$target['invoice_id'],
        'invoice_item_id' => (int)$target['invoice_item_id'],
    ] : null;
}

function create_student_fee_invoice(int $studentId, array $fee): array
{
    // Create one invoice containing one fee item, reusing an existing target if present.
    $existing = existing_student_fee_invoice_target($studentId, (int)$fee['id']);
    if ($existing) {
        return $existing;
    }

    $total = (float)$fee['amount'];
    $dueDate = !empty($fee['due_date']) ? $fee['due_date'] : date('Y-m-d', strtotime('+30 days'));
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $invoiceNumber = generate_invoice_number();
        $invoiceStmt = $pdo->prepare(
            'INSERT INTO invoices (invoice_number, student_id, total_amount, paid_amount, remaining_amount, status, due_date)
             VALUES (?, ?, ?, 0, ?, ?, ?)'
        );
        $invoiceStmt->execute([
            $invoiceNumber,
            $studentId,
            $total,
            $total,
            invoice_status(0, $total, $dueDate),
            $dueDate,
        ]);
        $invoiceId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO invoice_items (invoice_id, fee_id, fee_name, amount, fee_type) VALUES (?, ?, ?, ?, ?)'
        );
        $itemStmt->execute([$invoiceId, $fee['id'], $fee['fee_name'], $fee['amount'], $fee['fee_type']]);
        $invoiceItemId = (int)$pdo->lastInsertId();

        $pdo->commit();
        return ['invoice_id' => $invoiceId, 'invoice_item_id' => $invoiceItemId];
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function ensure_student_fee_invoice(int $studentId, int $feeId): array
{
    $fee = student_fee_for_payment($studentId, $feeId);
    if (!$fee) {
        throw new RuntimeException('Frais introuvable pour votre dossier.');
    }

    $summary = student_fee_payment_summary($studentId, $fee);
    if ($summary['status'] === 'payable') {
        return [
            'invoice_id' => (int)$summary['invoice_id'],
            'invoice_item_id' => (int)$summary['invoice_item_id'],
        ];
    }

    if ($summary['status'] === 'paid') {
        throw new RuntimeException('Ce frais est deja paye.');
    }

    if ($summary['status'] === 'pending') {
        throw new RuntimeException('Un paiement est deja en attente pour ce frais.');
    }

    if ($summary['status'] === 'blocked_registration') {
        throw new RuntimeException('L inscription doit etre totalement payee avant ce frais optionnel.');
    }

    return create_student_fee_invoice($studentId, $fee);
}

function matching_fees_for_student(array $student): array
{
    return query_all(
        "SELECT id, fee_name, program, level, academic_year, fee_type, amount, due_date, description, updated_at
         FROM fees
         WHERE (program = ? OR program = 'ALL') AND (level = ? OR level = 'ALL') AND academic_year = ?
           AND (due_date IS NULL OR due_date >= CURDATE())
         ORDER BY fee_type ASC, fee_name ASC, program ASC, level ASC",
        [$student['program'], $student['level'], $student['academic_year']]
    );
}

function ensure_automatic_student_invoices(int $studentId): void
{
    // Materialize all currently applicable fee invoices for one student.
    $student = query_one('SELECT * FROM students WHERE id = ?', [$studentId]);
    if (!$student) {
        return;
    }

    foreach (matching_fees_for_student($student) as $fee) {
        $summary = student_fee_payment_summary($studentId, $fee);
        if ($summary['status'] === 'not_invoiced') {
            create_student_fee_invoice($studentId, $fee);
        }
    }
}

function ensure_automatic_invoices_for_all_students(): void
{
    foreach (query_all('SELECT id FROM students') as $student) {
        ensure_automatic_student_invoices((int)$student['id']);
    }
}

function available_payment_targets(int $studentId, int $invoiceId): array
{
    if (!student_owns_invoice($studentId, $invoiceId)) {
        return [];
    }

    $items = query_all(
        'SELECT ii.*, f.due_date AS fee_due_date
         FROM invoice_items ii
         LEFT JOIN fees f ON f.id = ii.fee_id
         WHERE ii.invoice_id = ?
         ORDER BY ii.fee_type ASC, ii.id ASC',
        [$invoiceId]
    );
    $targets = [];

    foreach ($items as $item) {
        if (!empty($item['fee_due_date']) && $item['fee_due_date'] < date('Y-m-d')) {
            continue;
        }

        if (invoice_item_has_pending_payment((int)$item['id'])) {
            continue;
        }

        $remaining = invoice_item_remaining((int)$item['id'], false);
        if ($remaining <= 0.009) {
            continue;
        }
        if ($item['fee_name'] !== 'registration') {
            $fee = query_one('SELECT program, level, academic_year FROM fees WHERE id = ?', [$item['fee_id']]);
            if (
                $fee
                && !student_registration_paid_for_scope($studentId, $fee['program'], $fee['level'], $fee['academic_year'])
            ) {
                continue;
            }
        }
        $item['remaining_amount'] = $remaining;
        $targets[] = $item;
    }

    return $targets;
}

function receipt_for_payment(int $paymentId): ?array
{
    return query_one('SELECT * FROM receipts WHERE payment_id = ?', [$paymentId]);
}

function admin_dashboard_payload(): array
{
    // Build the live dashboard payload consumed by PHP rendering and JSON polling.
    ensure_automatic_invoices_for_all_students();
    ensure_monthly_stipends_for_all_students();
    sync_all_invoice_statuses();

    $stats = [
        'total_students' => (int)query_value('SELECT COUNT(*) FROM students'),
        'registered_students' => (int)query_value("SELECT COUNT(*) FROM students WHERE account_status = 'registered'"),
        'not_registered_students' => (int)query_value("SELECT COUNT(*) FROM students WHERE account_status = 'not_registered'"),
        'total_invoices' => (int)query_value('SELECT COUNT(*) FROM invoices'),
        'total_expected_amount' => (float)query_value('SELECT COALESCE(SUM(total_amount), 0) FROM invoices'),
        'total_paid_amount' => (float)query_value('SELECT COALESCE(SUM(paid_amount), 0) FROM invoices'),
        'total_unpaid_amount' => (float)query_value('SELECT COALESCE(SUM(remaining_amount), 0) FROM invoices'),
        'pending_payments' => (int)query_value("SELECT COUNT(*) FROM payments WHERE status = 'pending'"),
        'late_invoices' => (int)query_value("SELECT COUNT(*) FROM invoices WHERE status = 'late'"),
    ];

    $pendingPayments = query_all(
        "SELECT p.id, p.payment_reference, p.amount, p.status, p.submitted_at,
                s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                i.invoice_number
         FROM payments p
         JOIN students s ON s.id = p.student_id
         JOIN invoices i ON i.id = p.invoice_id
         WHERE p.status = 'pending'
         ORDER BY p.submitted_at DESC
         LIMIT 10"
    );

    return [
        'stats' => $stats,
        'pending_payments' => $pendingPayments,
    ];
}

function student_dashboard_payload(int $studentId): array
{
    // Build the live student dashboard payload consumed by PHP rendering and JSON polling.
    ensure_automatic_student_invoices($studentId);
    ensure_monthly_stipend_for_student($studentId);
    sync_all_invoice_statuses();

    $student = query_one('SELECT * FROM students WHERE id = ?', [$studentId]);
    if (!$student) {
        return ['student' => null, 'stats' => [], 'available_fees' => [], 'invoices' => [], 'payments' => [], 'stipend' => []];
    }

    $stipend = student_stipend_summary($studentId);

    $stats = [
        'total_invoiced_amount' => (float)query_value('SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
        'total_paid_amount' => (float)query_value('SELECT COALESCE(SUM(paid_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
        'remaining_balance' => (float)query_value('SELECT COALESCE(SUM(remaining_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
        'receipt_count' => (int)query_value('SELECT COUNT(*) FROM receipts WHERE student_id = ?', [$studentId]),
        'pending_payment_count' => (int)query_value("SELECT COUNT(*) FROM payments WHERE student_id = ? AND status = 'pending'", [$studentId]),
    ];

    $invoices = query_all(
        'SELECT id, invoice_number, total_amount, paid_amount, remaining_amount, status, due_date, created_at
         FROM invoices
         WHERE student_id = ?
         ORDER BY created_at DESC',
        [$studentId]
    );

    $availableFees = matching_fees_for_student($student);
    foreach ($availableFees as &$fee) {
        $summary = student_fee_payment_summary($studentId, $fee);
        $fee['payment_status'] = $summary['status'];
        $fee['can_pay'] = $summary['can_pay'];
        $fee['remaining_amount'] = $summary['remaining_amount'];
        $fee['invoice_id'] = $summary['invoice_id'];
        $fee['invoice_item_id'] = $summary['invoice_item_id'];
    }
    unset($fee);

    $payments = query_all(
        "SELECT p.id, p.payment_reference, p.amount, p.payment_method, p.status, p.submitted_at, p.validated_at,
                i.invoice_number, r.id AS receipt_id
         FROM payments p
         JOIN invoices i ON i.id = p.invoice_id
         LEFT JOIN receipts r ON r.payment_id = p.id
         WHERE p.student_id = ?
         ORDER BY p.submitted_at DESC
         LIMIT 10",
        [$studentId]
    );

    return [
        'student' => $student,
        'stats' => $stats,
        'stipend' => $stipend,
        'available_fees' => $availableFees,
        'invoices' => $invoices,
        'payments' => $payments,
    ];
}

function create_notification(string $recipientType, int $recipientId, string $title, string $message, ?string $linkUrl = null): void
{
    // Store a notification for either an agent or a student.
    if (!in_array($recipientType, ['admin', 'student'], true) || $recipientId <= 0) {
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO notifications (recipient_type, recipient_id, title, message, link_url)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$recipientType, $recipientId, $title, $message, $linkUrl]);
}

function notify_admins_payment_submitted(int $paymentId): void
{
    $payment = query_one(
        "SELECT p.id, p.payment_reference, p.amount,
                s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                i.invoice_number, ii.fee_name
         FROM payments p
         JOIN students s ON s.id = p.student_id
         JOIN invoices i ON i.id = p.invoice_id
         LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
         WHERE p.id = ?",
        [$paymentId]
    );
    if (!$payment) {
        return;
    }

    $title = tr('Nouveau paiement');
    $message = sprintf(
        '%s (%s) a soumis %s pour %s.',
        (string)$payment['student_name'],
        (string)$payment['matricule'],
        money($payment['amount']),
        (string)$payment['invoice_number']
    );
    $link = 'admin/validate_payment.php?id=' . (int)$payment['id'];

    foreach (query_all('SELECT id FROM admins') as $admin) {
        create_notification('admin', (int)$admin['id'], $title, $message, $link);
    }
}

function notify_student_payment_treated(int $paymentId): void
{
    $payment = query_one(
        "SELECT p.id, p.student_id, p.payment_reference, p.amount, p.status, p.rejection_reason,
                i.invoice_number, r.id AS receipt_id
         FROM payments p
         JOIN invoices i ON i.id = p.invoice_id
         LEFT JOIN receipts r ON r.payment_id = p.id
         WHERE p.id = ?",
        [$paymentId]
    );
    if (!$payment || !in_array($payment['status'], ['validated', 'rejected'], true)) {
        return;
    }

    if ($payment['status'] === 'validated') {
        $title = tr('Paiement valide');
        $message = sprintf(
            'Votre paiement %s de %s pour %s a ete valide.',
            (string)$payment['payment_reference'],
            money($payment['amount']),
            (string)$payment['invoice_number']
        );
        $link = !empty($payment['receipt_id'])
            ? 'student/receipt.php?id=' . (int)$payment['receipt_id']
            : 'student/payment_history.php';
    } else {
        $title = tr('Paiement rejete');
        $message = sprintf(
            'Votre paiement %s de %s pour %s a ete rejete.',
            (string)$payment['payment_reference'],
            money($payment['amount']),
            (string)$payment['invoice_number']
        );
        if (!empty($payment['rejection_reason'])) {
            $message .= ' Motif: ' . (string)$payment['rejection_reason'];
        }
        $link = 'student/payment_history.php';
    }

    create_notification('student', (int)$payment['student_id'], $title, $message, $link);
}

function current_notification_recipient(): ?array
{
    if (current_admin_id()) {
        return ['type' => 'admin', 'id' => current_admin_id()];
    }
    if (current_student_id()) {
        return ['type' => 'student', 'id' => current_student_id()];
    }

    return null;
}

function notification_payload(array $notification): array
{
    return [
        'id' => (int)$notification['id'],
        'title' => (string)$notification['title'],
        'message' => (string)$notification['message'],
        'url' => !empty($notification['link_url']) ? url((string)$notification['link_url']) : null,
        'is_read' => (bool)$notification['is_read'],
        'created_at' => (string)$notification['created_at'],
    ];
}

function notifications_for_recipient(string $recipientType, int $recipientId, int $limit = 10): array
{
    $limit = max(1, min(50, $limit));
    $rows = query_all(
        "SELECT id, title, message, link_url, is_read, created_at
         FROM notifications
         WHERE recipient_type = ? AND recipient_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT {$limit}",
        [$recipientType, $recipientId]
    );

    return array_map('notification_payload', $rows);
}

function unread_notification_count(string $recipientType, int $recipientId): int
{
    return (int)query_value(
        'SELECT COUNT(*) FROM notifications WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0',
        [$recipientType, $recipientId]
    );
}

function mark_notifications_read(string $recipientType, int $recipientId, ?int $notificationId = null): void
{
    if ($notificationId !== null && $notificationId > 0) {
        $stmt = db()->prepare(
            'UPDATE notifications
             SET is_read = 1, read_at = COALESCE(read_at, NOW())
             WHERE recipient_type = ? AND recipient_id = ? AND id = ?'
        );
        $stmt->execute([$recipientType, $recipientId, $notificationId]);
        return;
    }

    $stmt = db()->prepare(
        'UPDATE notifications
         SET is_read = 1, read_at = COALESCE(read_at, NOW())
         WHERE recipient_type = ? AND recipient_id = ? AND is_read = 0'
    );
    $stmt->execute([$recipientType, $recipientId]);
}
