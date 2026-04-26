(function () {
  const fallbackDictionaries = {
    ar: {
      theme_dark: 'الوضع الداكن',
      theme_light: 'الوضع الفاتح',
      switch_to_english: 'English',
      switch_to_arabic: 'العربية',
      notify_saved: 'تم حفظ الإعدادات بنجاح',
      notify_warning_msg: 'يوجد عناصر تحتاج متابعة عاجلة.',
      notify_title: 'النظام',
      calendar_fallback: 'تعذر تحميل التقويم. تحقق من الاتصال بالشبكة.'
    },
    en: {
      theme_dark: 'Dark mode',
      theme_light: 'Light mode',
      switch_to_english: 'English',
      switch_to_arabic: 'Arabic',
      notify_saved: 'Settings were saved successfully',
      notify_warning_msg: 'There are items that require immediate follow-up.',
      notify_title: 'System',
      calendar_fallback: 'Calendar failed to load. Please check your network connection.'
    }
  };

  const bootstrapHref = {
    rtl: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css',
    ltr: 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
  };

  const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  const state = {
    locale: document.documentElement.lang || localStorage.getItem('app_locale') || 'ar',
    theme: document.documentElement.getAttribute('data-theme') || localStorage.getItem('app_theme') || preferredTheme,
    dictionary: fallbackDictionaries.ar
  };

  const el = {
    bootstrapCss: document.getElementById('bootstrapCss'),
    localeToggle: document.getElementById('localeToggle'),
    mobileLocaleToggle: document.getElementById('mobileLocaleToggle'),
    themeToggle: document.getElementById('themeToggle'),
    mobileThemeToggle: document.getElementById('mobileThemeToggle'),
    successToast: document.getElementById('successToast'),
    warningToast: document.getElementById('warningToast'),
    calendarFallback: document.getElementById('calendarFallback'),
    sidebarToggle: document.getElementById('sidebarToggle'),
    appSidebar: document.getElementById('appSidebar')
  };

  let calendar;

  const isMobileViewport = () => window.innerWidth <= 991;

  const setSidebarOpen = (open) => {
    if (!el.appSidebar) return;
    el.appSidebar.classList.toggle('open', open);
    document.body.classList.toggle('mobile-sidebar-open', open && isMobileViewport());
  };

  const setDirection = (locale) => {
    const isArabic = locale === 'ar';
    document.documentElement.lang = locale;
    document.documentElement.dir = isArabic ? 'rtl' : 'ltr';
    document.body.setAttribute('dir', isArabic ? 'rtl' : 'ltr');
    document.body.classList.remove('dir-rtl', 'dir-ltr');
    document.body.classList.add(isArabic ? 'dir-rtl' : 'dir-ltr');
    if (el.bootstrapCss) el.bootstrapCss.setAttribute('href', isArabic ? bootstrapHref.rtl : bootstrapHref.ltr);
  };

  const syncSidebarForViewport = () => {
    if (!el.appSidebar) return;
    if (!isMobileViewport()) {
      setSidebarOpen(false);
      return;
    }

    document.body.classList.remove('sidebar-collapsed');
    document.body.classList.toggle('mobile-sidebar-open', el.appSidebar.classList.contains('open'));
  };

  const syncThemeButtons = (theme) => {
    const label = theme === 'dark'
      ? (state.dictionary.theme_light || 'Light mode')
      : (state.dictionary.theme_dark || 'Dark mode');

    if (el.themeToggle) el.themeToggle.textContent = label;
    if (el.mobileThemeToggle) el.mobileThemeToggle.textContent = label;
  };

  const syncLocaleButtons = (locale) => {
    const label = locale === 'ar'
      ? (state.dictionary.switch_to_english || 'English')
      : (state.dictionary.switch_to_arabic || 'Arabic');
    if (el.localeToggle) el.localeToggle.textContent = label;
    if (el.mobileLocaleToggle) el.mobileLocaleToggle.textContent = label;
  };

  const setTheme = (theme) => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('app_theme', theme);
    syncThemeButtons(theme);
  };

  const applyTranslations = (dict) => {
    state.dictionary = dict;

    document.querySelectorAll('[data-i18n]').forEach((node) => {
      const key = node.getAttribute('data-i18n');
      if (dict[key]) node.textContent = dict[key];
    });

    if (el.calendarFallback && dict.calendar_fallback) {
      el.calendarFallback.textContent = dict.calendar_fallback;
    }

    syncThemeButtons(state.theme);
  };

  const loadLocale = async (locale) => {
    let dict = fallbackDictionaries[locale] || fallbackDictionaries.ar;

    try {
      const response = await fetch(`/assets/theme/locales/${locale}/common.json`, { cache: 'no-store' });
      if (response.ok) dict = await response.json();
    } catch (error) {
      console.warn('Locale fetch fallback:', error);
    }

    applyTranslations(dict);
    setDirection(locale);
    syncLocaleButtons(locale);

    if (calendar) {
      calendar.setOption('locale', locale === 'ar' ? 'ar' : 'en');
      calendar.setOption('direction', locale === 'ar' ? 'rtl' : 'ltr');
      calendar.render();
    }

    localStorage.setItem('app_locale', locale);
  };

  const submitServerToggle = (formId) => {
    const form = document.getElementById(formId);
    if (!form) return false;
    form.submit();
    return true;
  };

  const initCalendar = () => {
    const calendarEl = document.getElementById('calendar');
    if (!window.FullCalendar || !calendarEl) return;

    try {
      calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: state.locale,
        direction: state.locale === 'ar' ? 'rtl' : 'ltr',
        headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,timeGridWeek' },
        events: []
      });
      calendar.render();
    } catch (error) {
      if (el.calendarFallback) el.calendarFallback.classList.remove('d-none');
    }
  };

  const toggleTheme = () => {
    state.theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    setTheme(state.theme);
    submitServerToggle(state.theme === 'dark' ? 'themeFormDark' : 'themeFormLight');
  };

  const toggleLocale = () => {
    state.locale = state.locale === 'ar' ? 'en' : 'ar';
    loadLocale(state.locale);
    submitServerToggle(state.locale === 'ar' ? 'localeFormAr' : 'localeFormEn');
  };

  const toggleSidebar = () => {
    if (!el.appSidebar) return;
    if (isMobileViewport()) {
      setSidebarOpen(!el.appSidebar.classList.contains('open'));
      return;
    }

    document.body.classList.toggle('sidebar-collapsed');
  };

  const bindSidebarCloseBehavior = () => {
    if (!el.appSidebar) return;

    document.addEventListener('click', (event) => {
      if (!isMobileViewport() || !el.appSidebar.classList.contains('open')) return;
      if (el.appSidebar.contains(event.target) || el.sidebarToggle?.contains(event.target)) return;
      setSidebarOpen(false);
    });

    el.appSidebar.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (isMobileViewport()) setSidebarOpen(false);
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && isMobileViewport() && el.appSidebar.classList.contains('open')) {
        setSidebarOpen(false);
      }
    });
  };

  const bindEvents = () => {
    if (el.localeToggle) el.localeToggle.addEventListener('click', toggleLocale);
    if (el.mobileLocaleToggle) el.mobileLocaleToggle.addEventListener('click', toggleLocale);
    if (el.themeToggle) el.themeToggle.addEventListener('click', toggleTheme);
    if (el.mobileThemeToggle) el.mobileThemeToggle.addEventListener('click', toggleTheme);
    if (el.sidebarToggle) el.sidebarToggle.addEventListener('click', toggleSidebar);
    window.addEventListener('resize', syncSidebarForViewport);
    bindSidebarCloseBehavior();

    if (el.successToast) {
      el.successToast.addEventListener('click', () => toastr.success(state.dictionary.notify_saved, state.dictionary.notify_title));
    }

    if (el.warningToast) {
      el.warningToast.addEventListener('click', () => toastr.warning(state.dictionary.notify_warning_msg, state.dictionary.notify_title));
    }
  };

  const init = async () => {
    setDirection(state.locale);
    setTheme(state.theme);
    initCalendar();
    bindEvents();
    await loadLocale(state.locale);
    syncSidebarForViewport();
  };

  init();
})();
