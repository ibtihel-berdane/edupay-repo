// Formatting and escaping helpers used by dynamic dashboard and notification rendering.
function formatMoney(value) {
    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

const TRANSLATIONS = {
    // Client-side copy mirrors the PHP translation catalog for content rendered by JavaScript.
    en: {
        'Francais': 'French',
        'Langue': 'Language',
        'Nuit': 'Night',
        'Jour': 'Day',
        'Activer le mode nuit': 'Turn on night mode',
        'Activer le mode jour': 'Turn on day mode',
        'Mode nuit': 'Night mode',
        'Mode jour': 'Day mode',
        'Notifications': 'Notifications',
        'Tout lu': 'Mark all read',
        'Chargement...': 'Loading...',
        'Aucune notification.': 'No notifications.',
        'Deconnexion': 'Logout',
        'EduPay+ gestion comptable des etudiants': 'EduPay+ student accounting management',
        'Tableau de bord': 'Dashboard',
        'Etudiants': 'Students',
        'Frais': 'Fees',
        'Factures': 'Invoices',
        'Paiements': 'Payments',
        'Recus': 'Receipts',
        'Rapports': 'Reports',
        'Bourse': 'Stipend',
        'Bourses': 'Stipends',
        'Profil': 'Profile',
        'Bienvenue': 'Welcome',
        'La plateforme de gestion comptable dediee aux etudiants et agents.': 'The accounting management platform for students and agents.',
        'Connexion': 'Login',
        'Inscription': 'Registration',
        'Gestion comptable des etudiants': 'Student accounting management',
        'Acces etudiants et agents comptables.': 'Student and accounting agent access.',
        'Matricule': 'ID number',
        'Mot de passe': 'Password',
        'Se connecter': 'Log in',
        'Pas encore de compte ?': 'No account yet?',
        'Inscrivez-vous': 'Sign up',
        'Deja un compte ?': 'Already have an account?',
        'Connectez-vous': 'Log in',
        'Ajouter un frais': 'Add a fee',
        'Nom du frais': 'Fee name',
        'Montant': 'Amount',
        'Filiere': 'Program',
        'Niveau': 'Level',
        'Annee academique': 'Academic year',
        'Date limite de paiement': 'Payment deadline',
        'Description': 'Description',
        'Enregistrer': 'Save',
        'Retour': 'Back',
        'Selectionner une filiere': 'Select a program',
        'Selectionner un niveau': 'Select a level',
        'Transport': 'Transport',
        'Hebergement': 'Housing',
        'Tableau de bord agent': 'Agent dashboard',
        'Vue financiere et paiements en attente.': 'Financial overview and pending payments.',
        'Ajouter un etudiant': 'Add a student',
        'Total etudiants': 'Total students',
        'Etudiants inscrits': 'Registered students',
        'Non inscrits': 'Not registered',
        'Total factures': 'Total invoices',
        'Montant attendu': 'Expected amount',
        'Montant paye': 'Paid amount',
        'Montant impaye': 'Unpaid amount',
        'Paiements en attente': 'Pending payments',
        'Factures en retard': 'Late invoices',
        'Tout voir': 'View all',
        'Reference': 'Reference',
        'Facture': 'Invoice',
        'Statut': 'Status',
        'Examiner': 'Review',
        'Aucun paiement en attente.': 'No pending payments.',
        'Tableau de bord etudiant': 'Student dashboard',
        'Payer une facture': 'Pay an invoice',
        'Total facture': 'Total billed',
        'Total': 'Total',
        'Total paye': 'Total paid',
        'Solde restant': 'Remaining balance',
        'Frais disponibles': 'Available fees',
        'Frais correspondant a votre filiere, niveau et annee academique.': 'Fees matching your program, level, and academic year.',
        'Type': 'Type',
        'Date limite': 'Deadline',
        'Echeance': 'Due date',
        'Payer': 'Pay',
        'Paye': 'Paid',
        'Reste': 'Remaining',
        'En attente': 'Pending',
        "Payez l'inscription d'abord": 'Pay registration first',
        'Payez l inscription d abord': 'Pay registration first',
        'Aucun frais disponible pour votre dossier.': 'No fee is available for your record.',
        'Voir': 'View',
        'Aucune facture disponible.': 'No invoice available.',
        'Paiements recents': 'Recent payments',
        'Recu': 'Receipt',
        'Aucun paiement recent.': 'No recent payment.',
        'Payee': 'Paid',
        'Non payee': 'Unpaid',
        'Partiellement payee': 'Partially paid',
        'En retard': 'Late',
        'Valide': 'Validated',
        'Rejete': 'Rejected',
        'Inscrit': 'Registered',
        'Non inscrit': 'Not registered',
        'Obligatoire': 'Required',
        'Optionnel': 'Optional',
        'A payer': 'To pay',
        'Disponible': 'Available',
        'Inscription requise': 'Registration required',
        'Date limite depassee': 'Deadline passed',
        'Bourse versee': 'Stipend paid',
        'Aucune bourse versee.': 'No stipend paid.',
        'Attribuee': 'Assigned',
        'Non attribuee': 'Not assigned',
        'Confirmer le mot de passe': 'Confirm password',
        'Votre dossier financier doit deja exister.': 'Your financial record must already exist.',
        'Creer le compte': 'Create account',
        'Etape 2': 'Step 2',
        'Inscription etudiant': 'Student registration',
        'Inscription agent': 'Agent registration',
        "Renseignez les informations de l'agent comptable.": 'Enter the accounting agent information.',
        'Attribution': 'Assignment',
        'Ce mois': 'This month',
        'Montant du mois': 'Monthly amount',
        'Total recu': 'Total received',
        'Montant eligible': 'Eligible amount',
        'Mois courant': 'Current month',
        'Prochaine bourse': 'Next stipend',
        'En attente d attribution': 'Waiting for assignment',
        'Historique des bourses': 'Stipend history',
        'Mois': 'Month',
        'Date de versement': 'Disbursement date',
        'Attribution et suivi des bourses mensuelles.': 'Monthly stipend assignment and tracking.',
        'Attribuees': 'Assigned',
        'Non attribuees': 'Not assigned',
        'A verser ce mois': 'To disburse this month',
        'Total deja verse': 'Total already disbursed',
        'Recherche': 'Search',
        'Tous': 'All',
        'Rechercher': 'Search',
        'Effacer': 'Clear',
        'Deja verse': 'Already disbursed',
        'Montant mensuel': 'Monthly amount',
        'Supprimer la bourse': 'Remove stipend',
        'Attribuer la bourse': 'Assign stipend',
        'Aucun etudiant trouve.': 'No student found.',
        'Prenom': 'First name',
        'Nom': 'Last name',
        'Telephone': 'Phone',
        'Methode': 'Method',
        'informatique': 'Computer science',
        'mathematique': 'Mathematics',
        'ST': 'Science and technology',
        'Biologie': 'Biology',
        'Licence1': 'Licence 1',
        'licence 2': 'Licence 2',
        'licence3': 'Licence 3',
        'master1': 'Master 1',
        'master2': 'Master 2',
        'L inscription est obligatoire. Transport et hebergement sont optionnels.': 'Registration is mandatory. Transport and housing are optional.',
        'Carte Edahabia': 'Edahabia card',
        'Virement bancaire': 'Bank transfer',
        'Depot especes': 'Cash deposit',
        'En ligne': 'Online',
        'Cheque': 'Check'
    },
    ar: {
        'Francais': 'الفرنسية',
        'Langue': 'اللغة',
        'Nuit': 'ليلي',
        'Jour': 'نهاري',
        'Activer le mode nuit': 'تفعيل الوضع الليلي',
        'Activer le mode jour': 'تفعيل الوضع النهاري',
        'Mode nuit': 'الوضع الليلي',
        'Mode jour': 'الوضع النهاري',
        'Notifications': 'الإشعارات',
        'Tout lu': 'تحديد الكل كمقروء',
        'Chargement...': 'جار التحميل...',
        'Aucune notification.': 'لا توجد إشعارات.',
        'Deconnexion': 'تسجيل الخروج',
        'EduPay+ gestion comptable des etudiants': 'EduPay+ لتسيير محاسبة الطلبة',
        'Tableau de bord': 'لوحة التحكم',
        'Etudiants': 'الطلبة',
        'Frais': 'الرسوم',
        'Factures': 'الفواتير',
        'Paiements': 'المدفوعات',
        'Recus': 'الوصولات',
        'Rapports': 'التقارير',
        'Bourse': 'المنحة',
        'Bourses': 'المنح',
        'Profil': 'الملف الشخصي',
        'Bienvenue': 'مرحبا',
        'La plateforme de gestion comptable dediee aux etudiants et agents.': 'منصة تسيير محاسبي مخصصة للطلبة والأعوان.',
        'Connexion': 'تسجيل الدخول',
        'Inscription': 'التسجيل',
        'Gestion comptable des etudiants': 'تسيير محاسبة الطلبة',
        'Acces etudiants et agents comptables.': 'دخول الطلبة والأعوان المحاسبين.',
        'Matricule': 'رقم التسجيل',
        'Mot de passe': 'كلمة المرور',
        'Se connecter': 'دخول',
        'Pas encore de compte ?': 'ليس لديك حساب؟',
        'Inscrivez-vous': 'سجل الآن',
        'Deja un compte ?': 'لديك حساب؟',
        'Connectez-vous': 'سجل الدخول',
        'Ajouter un frais': 'إضافة رسم',
        'Nom du frais': 'اسم الرسم',
        'Montant': 'المبلغ',
        'Filiere': 'الشعبة',
        'Niveau': 'المستوى',
        'Annee academique': 'السنة الجامعية',
        'Date limite de paiement': 'آخر أجل للدفع',
        'Description': 'الوصف',
        'Enregistrer': 'حفظ',
        'Retour': 'رجوع',
        'Selectionner une filiere': 'اختر شعبة',
        'Selectionner un niveau': 'اختر مستوى',
        'Transport': 'النقل',
        'Hebergement': 'الإيواء',
        'Tableau de bord agent': 'لوحة تحكم العون',
        'Vue financiere et paiements en attente.': 'نظرة مالية والمدفوعات المعلقة.',
        'Ajouter un etudiant': 'إضافة طالب',
        'Total etudiants': 'إجمالي الطلبة',
        'Etudiants inscrits': 'الطلبة المسجلون',
        'Non inscrits': 'غير المسجلين',
        'Total factures': 'إجمالي الفواتير',
        'Montant attendu': 'المبلغ المتوقع',
        'Montant paye': 'المبلغ المدفوع',
        'Montant impaye': 'المبلغ غير المدفوع',
        'Paiements en attente': 'مدفوعات معلقة',
        'Factures en retard': 'فواتير متأخرة',
        'Tout voir': 'عرض الكل',
        'Reference': 'المرجع',
        'Facture': 'الفاتورة',
        'Statut': 'الحالة',
        'Examiner': 'مراجعة',
        'Aucun paiement en attente.': 'لا توجد مدفوعات معلقة.',
        'Tableau de bord etudiant': 'لوحة تحكم الطالب',
        'Payer une facture': 'دفع فاتورة',
        'Total facture': 'إجمالي الفواتير',
        'Total': 'الإجمالي',
        'Total paye': 'إجمالي المدفوع',
        'Solde restant': 'الرصيد المتبقي',
        'Frais disponibles': 'الرسوم المتاحة',
        'Frais correspondant a votre filiere, niveau et annee academique.': 'الرسوم المطابقة لشعبتك ومستواك وسنتك الجامعية.',
        'Type': 'النوع',
        'Date limite': 'آخر أجل',
        'Echeance': 'تاريخ الاستحقاق',
        'Payer': 'دفع',
        'Paye': 'مدفوع',
        'Reste': 'الباقي',
        'En attente': 'معلق',
        "Payez l'inscription d'abord": 'ادفع رسوم التسجيل أولا',
        'Payez l inscription d abord': 'ادفع رسوم التسجيل أولا',
        'Aucun frais disponible pour votre dossier.': 'لا توجد رسوم متاحة لملفك.',
        'Voir': 'عرض',
        'Aucune facture disponible.': 'لا توجد فواتير.',
        'Paiements recents': 'المدفوعات الأخيرة',
        'Recu': 'وصل',
        'Aucun paiement recent.': 'لا توجد مدفوعات حديثة.',
        'Payee': 'مدفوعة',
        'Non payee': 'غير مدفوعة',
        'Partiellement payee': 'مدفوعة جزئيا',
        'En retard': 'متأخرة',
        'Valide': 'تم التحقق',
        'Rejete': 'مرفوض',
        'Inscrit': 'مسجل',
        'Non inscrit': 'غير مسجل',
        'Obligatoire': 'إجباري',
        'Optionnel': 'اختياري',
        'A payer': 'للدفع',
        'Disponible': 'متاح',
        'Inscription requise': 'التسجيل مطلوب',
        'Date limite depassee': 'انتهى الأجل',
        'Bourse versee': 'تم صرف المنحة',
        'Aucune bourse versee.': 'لا توجد منحة مصروفة.',
        'Attribuee': 'ممنوحة',
        'Non attribuee': 'غير ممنوحة',
        'Confirmer le mot de passe': 'تأكيد كلمة المرور',
        'Votre dossier financier doit deja exister.': 'يجب أن يكون ملفك المالي موجودا مسبقا.',
        'Creer le compte': 'إنشاء الحساب',
        'Etape 2': 'المرحلة 2',
        'Inscription etudiant': 'تسجيل الطالب',
        'Inscription agent': 'تسجيل العون',
        "Renseignez les informations de l'agent comptable.": 'أدخل معلومات العون المحاسب.',
        'Attribution': 'الإسناد',
        'Ce mois': 'هذا الشهر',
        'Montant du mois': 'مبلغ الشهر',
        'Total recu': 'إجمالي المستلم',
        'Montant eligible': 'المبلغ المستحق',
        'Mois courant': 'الشهر الحالي',
        'Prochaine bourse': 'المنحة القادمة',
        'En attente d attribution': 'في انتظار الإسناد',
        'Historique des bourses': 'سجل المنح',
        'Mois': 'الشهر',
        'Date de versement': 'تاريخ الصرف',
        'Attribution et suivi des bourses mensuelles.': 'إسناد ومتابعة المنح الشهرية.',
        'Attribuees': 'ممنوحة',
        'Non attribuees': 'غير ممنوحة',
        'A verser ce mois': 'للصرف هذا الشهر',
        'Total deja verse': 'إجمالي المصروف سابقا',
        'Recherche': 'بحث',
        'Tous': 'الكل',
        'Rechercher': 'بحث',
        'Effacer': 'مسح',
        'Deja verse': 'مصروف سابقا',
        'Montant mensuel': 'المبلغ الشهري',
        'Supprimer la bourse': 'حذف المنحة',
        'Attribuer la bourse': 'إسناد المنحة',
        'Aucun etudiant trouve.': 'لم يتم العثور على أي طالب.',
        'Prenom': 'الاسم',
        'Nom': 'اللقب',
        'Telephone': 'الهاتف',
        'Methode': 'الطريقة',
        'informatique': 'الإعلام الآلي',
        'mathematique': 'الرياضيات',
        'ST': 'علوم وتكنولوجيا',
        'Biologie': 'البيولوجيا',
        'Licence1': 'ليسانس 1',
        'licence 2': 'ليسانس 2',
        'licence3': 'ليسانس 3',
        'master1': 'ماستر 1',
        'master2': 'ماستر 2',
        'L inscription est obligatoire. Transport et hebergement sont optionnels.': 'التسجيل إجباري. النقل والإيواء اختياريان.',
        'Carte Edahabia': 'بطاقة الذهبية',
        'Virement bancaire': 'تحويل بنكي',
        'Depot especes': 'إيداع نقدي',
        'En ligne': 'عبر الإنترنت',
        'Cheque': 'شيك'
    }
};

function currentLang() {
    return window.EDUPAY_LANGUAGE || document.documentElement.lang || 'fr';
}

function tr(value) {
    const text = String(value ?? '');
    const dictionary = TRANSLATIONS[currentLang()] || {};
    return dictionary[text] || text;
}

function translateStaticPage(root = document.body) {
    if (currentLang() === 'fr' || !root) {
        return;
    }

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            const parent = node.parentElement;
            if (!parent || ['SCRIPT', 'STYLE', 'TEXTAREA'].includes(parent.tagName)) {
                return NodeFilter.FILTER_REJECT;
            }
            return node.nodeValue.trim() ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
        }
    });

    const nodes = [];
    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach((node) => {
        const raw = node.nodeValue;
        const trimmed = raw.replace(/\s+/g, ' ').trim();
        const translated = tr(trimmed);
        if (translated !== trimmed) {
            node.nodeValue = raw.replace(trimmed, translated);
        }
    });

    root.querySelectorAll('[placeholder], [title], [aria-label]').forEach((node) => {
        ['placeholder', 'title', 'aria-label'].forEach((attribute) => {
            const value = node.getAttribute(attribute);
            if (value) {
                node.setAttribute(attribute, tr(value));
            }
        });
    });
}

function badge(status) {
    // Render the same status badges that PHP produces on first page load.
    const labels = {
        paid: tr('Payee'),
        unpaid: tr('Non payee'),
        partially_paid: tr('Partiellement payee'),
        late: tr('En retard'),
        pending: tr('En attente'),
        validated: tr('Valide'),
        rejected: tr('Rejete'),
        registered: tr('Inscrit'),
        not_registered: tr('Non inscrit'),
        obligatory: tr('Obligatoire'),
        optional: tr('Optionnel'),
        payable: tr('A payer'),
        not_invoiced: tr('Disponible'),
        blocked_registration: tr('Inscription requise'),
        expired: tr('Date limite depassee'),
        disbursed: tr('Bourse versee'),
        assigned: tr('Attribuee'),
        not_assigned: tr('Non attribuee')
    };
    const safeClass = String(status || '').replace(/[^a-z0-9_-]/gi, '') || 'unknown';
    if (labels[status]) {
        return `<span class="badge badge-${safeClass}">${escapeHtml(labels[status])}</span>`;
    }
    const label = String(status || '').replaceAll('_', ' ');
    return `<span class="badge badge-${safeClass}">${escapeHtml(label.replace(/\b\w/g, c => c.toUpperCase()))}</span>`;
}

function feeLabel(feeName) {
    return {
        registration: tr('Inscription'),
        transport: tr('Transport'),
        housing: tr('Hebergement')
    }[feeName] || String(feeName || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function emptyRow(columns, text) {
    return `<tr><td colspan="${columns}" class="muted">${escapeHtml(tr(text))}</td></tr>`;
}

function renderFeeAction(fee, csrfToken) {
    if (fee.can_pay) {
        return `
            <form method="post" action="../student/pay_fee.php">
                <input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
                <input type="hidden" name="fee_id" value="${escapeHtml(fee.id)}">
                <button class="btn btn-primary btn-small" type="submit">${escapeHtml(tr('Payer'))}</button>
            </form>
        `;
    }

    if (fee.payment_status === 'paid') {
        return `<button class="btn btn-secondary btn-small" type="button" disabled>${escapeHtml(tr('Paye'))}</button>`;
    }

    if (fee.payment_status === 'pending') {
        return `<button class="btn btn-secondary btn-small" type="button" disabled>${escapeHtml(tr('En attente'))}</button>`;
    }

    if (fee.payment_status === 'blocked_registration') {
        return `<span class="muted">${escapeHtml(tr('Payez l inscription d abord'))}</span>`;
    }

    if (fee.payment_status === 'expired') {
        return `<span class="muted">${escapeHtml(tr('Date limite depassee'))}</span>`;
    }

    return '';
}

function feeRowClass(status) {
    return ['paid', 'pending', 'blocked_registration', 'expired'].includes(status)
        ? ` class="fee-row-locked fee-row-${escapeHtml(status)}"`
        : '';
}

function setText(selector, value) {
    document.querySelectorAll(selector).forEach((node) => {
        node.textContent = value;
    });
}

function setTheme(theme) {
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle('theme-dark', isDark);
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        button.setAttribute('aria-label', isDark ? tr('Activer le mode jour') : tr('Activer le mode nuit'));
        button.title = isDark ? tr('Mode jour') : tr('Mode nuit');
        const label = button.querySelector('[data-theme-label]');
        if (label) {
            label.textContent = isDark ? tr('Jour') : tr('Nuit');
        }
        const icon = button.querySelector('[data-theme-icon]');
        if (icon) {
            icon.classList.toggle('fa-moon', !isDark);
            icon.classList.toggle('fa-sun', isDark);
        }
    });
}

function initLanguageSwitcher() {
    document.querySelectorAll('[data-language-select]').forEach((select) => {
        select.addEventListener('change', () => {
            select.form?.submit();
        });
    });
}

function initThemeToggle() {
    const initialTheme = document.documentElement.classList.contains('theme-dark') ? 'dark' : 'light';
    setTheme(initialTheme);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = document.documentElement.classList.contains('theme-dark') ? 'light' : 'dark';
            try {
                localStorage.setItem('edupay-theme', nextTheme);
            } catch (error) {}
            setTheme(nextTheme);
        });
    });
}

function formatNotificationDate(value) {
    if (!value) {
        return '';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function notificationMarkup(notification) {
    const unreadClass = notification.is_read ? '' : ' unread';
    const body = `
        <strong>${escapeHtml(tr(notification.title))}</strong>
        <span>${escapeHtml(notification.message)}</span>
        <time>${escapeHtml(formatNotificationDate(notification.created_at))}</time>
    `;

    if (notification.url) {
        return `<a class="notification-item${unreadClass}" href="${escapeHtml(notification.url)}" data-notification-id="${escapeHtml(notification.id)}">${body}</a>`;
    }

    return `<div class="notification-item${unreadClass}" data-notification-id="${escapeHtml(notification.id)}">${body}</div>`;
}

function renderNotifications(center, data) {
    const countNode = center.querySelector('[data-notification-count]');
    const listNode = center.querySelector('[data-notification-list]');
    const count = Number(data.unread_count || 0);

    if (countNode) {
        countNode.textContent = count > 99 ? '99+' : String(count);
        countNode.hidden = count === 0;
    }

    if (listNode) {
        const notifications = data.notifications || [];
        listNode.innerHTML = notifications.length
            ? notifications.map(notificationMarkup).join('')
            : `<p class="notification-empty">${escapeHtml(tr('Aucune notification.'))}</p>`;
    }
}

function postNotificationAction(center, action, notificationId = null) {
    const params = new URLSearchParams();
    params.append('csrf_token', center.dataset.csrfToken || '');
    params.append('action', action);
    if (notificationId !== null) {
        params.append('notification_id', notificationId);
    }

    return fetch(center.dataset.notificationsApi, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    }).then((response) => response.ok ? response.json() : Promise.reject(response));
}

function showNotificationToast(notification) {
    let container = document.querySelector('[data-notification-toasts]');
    if (!container) {
        container = document.createElement('div');
        container.className = 'notification-toasts';
        container.dataset.notificationToasts = 'true';
        document.body.appendChild(container);
    }

    const toast = document.createElement(notification.url ? 'a' : 'div');
    toast.className = 'notification-toast';
    if (notification.url) {
        toast.href = notification.url;
    }
    toast.innerHTML = `
        <strong>${escapeHtml(tr(notification.title))}</strong>
        <span>${escapeHtml(notification.message)}</span>
    `;
    container.appendChild(toast);

    window.setTimeout(() => {
        toast.remove();
        if (!container.children.length) {
            container.remove();
        }
    }, 7000);
}

function renderAdminDashboard(data) {
    // Refresh agent dashboard cards and pending-payment rows from the polling API.
    const stats = data.stats || {};
    Object.keys(stats).forEach((key) => {
        const value = key.includes('amount') ? formatMoney(stats[key]) : stats[key];
        setText(`[data-stat="${key}"]`, value);
    });

    const pendingBody = document.querySelector('[data-admin-pending-payments]');
    if (pendingBody) {
        const payments = data.pending_payments || [];
        pendingBody.innerHTML = payments.length ? payments.map((payment) => `
            <tr>
                <td>${escapeHtml(payment.payment_reference)}</td>
                <td>${escapeHtml(payment.matricule)}</td>
                <td>${escapeHtml(payment.invoice_number)}</td>
                <td>${formatMoney(payment.amount)}</td>
                <td>${badge(payment.status)}</td>
                <td><a class="btn btn-secondary btn-small" href="../admin/validate_payment.php?id=${encodeURIComponent(payment.id)}">${escapeHtml(tr('Examiner'))}</a></td>
            </tr>
        `).join('') : emptyRow(6, 'Aucun paiement en attente.');
    }
}

function renderStudentDashboard(data) {
    // Refresh student dashboard cards, fee rows, invoices, payments, and bourse widgets.
    const dashboard = document.querySelector('[data-dashboard-api]');
    const csrfToken = dashboard ? dashboard.dataset.csrfToken : '';
    const stats = data.stats || {};
    Object.keys(stats).forEach((key) => {
        const value = key.includes('amount') || key.includes('balance') ? formatMoney(stats[key]) : stats[key];
        setText(`[data-stat="${key}"]`, value);
    });

    const stipend = data.stipend || {};
    setText('[data-stipend="current_month_amount"]', `${formatMoney(stipend.current_month_amount)} DA`);
    setText('[data-stipend="next_stipend_date"]', stipend.next_stipend_date || '');
    setText('[data-stipend="total_received"]', `${formatMoney(stipend.total_received)} DA`);
    document.querySelectorAll('[data-stipend="current_month_received"]').forEach((node) => {
        node.innerHTML = stipend.current_month_received ? badge('disbursed') : badge('pending');
    });

    const stipendBody = document.querySelector('[data-student-stipend-history]');
    if (stipendBody) {
        const history = stipend.history || [];
        stipendBody.innerHTML = history.length ? history.map((row) => `
            <tr>
                <td>${escapeHtml(row.stipend_month)}</td>
                <td>${formatMoney(row.amount)} DA</td>
                <td>${escapeHtml(row.level_group)}</td>
                <td>${badge(row.status)}</td>
                <td>${escapeHtml(row.disbursed_at || '')}</td>
            </tr>
        `).join('') : emptyRow(5, 'Aucune bourse versee.');
    }

    const feesBody = document.querySelector('[data-student-fees]');
    if (feesBody) {
        const fees = data.available_fees || [];
        feesBody.innerHTML = fees.length ? fees.map((fee) => `
            <tr${feeRowClass(fee.payment_status)}>
                <td>${escapeHtml(feeLabel(fee.fee_name))}</td>
                <td>${badge(fee.fee_type)}</td>
                <td>${formatMoney(fee.amount)}</td>
                <td>${escapeHtml(fee.due_date || '')}</td>
                <td>${badge(fee.payment_status)}</td>
                <td>${escapeHtml(fee.description || '')}</td>
                <td>${renderFeeAction(fee, csrfToken)}</td>
            </tr>
        `).join('') : emptyRow(7, 'Aucun frais disponible pour votre dossier.');
    }

    const invoicesBody = document.querySelector('[data-student-invoices]');
    if (invoicesBody) {
        const invoices = data.invoices || [];
        invoicesBody.innerHTML = invoices.length ? invoices.map((invoice) => `
            <tr>
                <td>${escapeHtml(invoice.invoice_number)}</td>
                <td>${formatMoney(invoice.total_amount)}</td>
                <td>${formatMoney(invoice.paid_amount)}</td>
                <td>${formatMoney(invoice.remaining_amount)}</td>
                <td>${badge(invoice.status)}</td>
                <td class="actions">
                    <a class="btn btn-secondary btn-small" href="../student/view_invoice.php?id=${encodeURIComponent(invoice.id)}">${escapeHtml(tr('Voir'))}</a>
                    <a class="btn btn-secondary btn-small" href="../student/invoice_pdf.php?id=${encodeURIComponent(invoice.id)}">PDF</a>
                </td>
            </tr>
        `).join('') : emptyRow(6, 'Aucune facture disponible.');
    }

    const paymentsBody = document.querySelector('[data-student-payments]');
    if (paymentsBody) {
        const payments = data.payments || [];
        paymentsBody.innerHTML = payments.length ? payments.map((payment) => `
            <tr>
                <td>${escapeHtml(payment.payment_reference)}</td>
                <td>${escapeHtml(payment.invoice_number)}</td>
                <td>${formatMoney(payment.amount)}</td>
                <td>${badge(payment.status)}</td>
                <td>${payment.receipt_id ? `<a class="btn btn-secondary btn-small" href="../student/receipt.php?id=${encodeURIComponent(payment.receipt_id)}">${escapeHtml(tr('Recu'))}</a>` : ''}</td>
            </tr>
        `).join('') : emptyRow(5, 'Aucun paiement recent.');
    }
}

function startDashboardPolling() {
    // Poll dashboard APIs so status changes appear without a full page reload.
    const dashboard = document.querySelector('[data-dashboard-api]');
    if (!dashboard) {
        return;
    }

    const type = dashboard.dataset.dashboardType;
    const api = dashboard.dataset.dashboardApi;
    const refresh = () => {
        fetch(api, { credentials: 'same-origin' })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((data) => {
                if (type === 'admin') {
                    renderAdminDashboard(data);
                }
                if (type === 'student') {
                    renderStudentDashboard(data);
                }
            })
            .catch(() => {});
    };

    refresh();
    window.setInterval(refresh, 5000);
}

function startNotifications() {
    // Poll notifications and handle mark-as-read interactions.
    const center = document.querySelector('[data-notification-center]');
    if (!center) {
        return;
    }

    const toggle = center.querySelector('[data-notification-toggle]');
    const menu = center.querySelector('[data-notification-menu]');
    const markAll = center.querySelector('[data-notification-mark-all]');
    const list = center.querySelector('[data-notification-list]');
    let initialized = false;
    let latestKnownId = 0;

    const closeMenu = () => {
        if (menu) {
            menu.hidden = true;
        }
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    };

    const refresh = () => {
        fetch(center.dataset.notificationsApi, { credentials: 'same-origin' })
            .then((response) => response.ok ? response.json() : Promise.reject(response))
            .then((data) => {
                renderNotifications(center, data);

                const notifications = data.notifications || [];
                const currentLatestId = Number(data.latest_id || notifications.reduce((max, item) => Math.max(max, Number(item.id || 0)), 0));
                if (initialized) {
                    notifications
                        .filter((notification) => !notification.is_read && Number(notification.id) > latestKnownId)
                        .reverse()
                        .forEach(showNotificationToast);
                }
                latestKnownId = Math.max(latestKnownId, currentLatestId);
                initialized = true;
            })
            .catch(() => {});
    };

    if (toggle && menu) {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            menu.hidden = !menu.hidden;
            toggle.setAttribute('aria-expanded', menu.hidden ? 'false' : 'true');
        });

        menu.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        document.addEventListener('click', closeMenu);
    }

    if (markAll) {
        markAll.addEventListener('click', () => {
            postNotificationAction(center, 'mark_all')
                .then((data) => renderNotifications(center, data))
                .catch(() => {});
        });
    }

    if (list) {
        list.addEventListener('click', (event) => {
            const item = event.target.closest('[data-notification-id]');
            if (!item) {
                return;
            }

            const notificationId = item.dataset.notificationId;
            const link = item.closest('a[href]');
            const modifiedClick = event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;

            if (link && !modifiedClick) {
                event.preventDefault();
                postNotificationAction(center, 'mark_one', notificationId)
                    .catch(() => {})
                    .finally(() => {
                        window.location.href = link.href;
                    });
                return;
            }

            postNotificationAction(center, 'mark_one', notificationId)
                .then((data) => renderNotifications(center, data))
                .catch(() => {});
        });
    }

    refresh();
    window.setInterval(refresh, 4000);
}

document.addEventListener('DOMContentLoaded', () => {
    initLanguageSwitcher();
    initThemeToggle();
    translateStaticPage();
    startDashboardPolling();
    startNotifications();
});
