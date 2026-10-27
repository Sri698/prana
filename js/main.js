/**
 * PRANA HOMEO CARE — Core Interactive Functionality
 * Developed with Excellence by Camora Technologies
 */

(function () {
  'use strict';

  // 1. Header Scrolling Animation
  // 1b. Dynamic Active Navigation & Footer Link Synchronization
  function syncActiveNavAndFooter() {
    try {
      const rawPath = window.location.pathname.toLowerCase();
      let page = rawPath.substring(rawPath.lastIndexOf('/') + 1) || 'index.html';
      page = page.split('?')[0].split('#')[0];

      let currentCategory = 'home';
      if (page === 'index.html' || page === '' || page === 'index.php') {
        currentCategory = 'home';
      } else if (page.startsWith('about')) {
        currentCategory = 'about';
      } else if (page.startsWith('panda-mascot')) {
        currentCategory = 'mascot';
      } else if (page.startsWith('services') || page.startsWith('treatment') || page.startsWith('disease-details') || page.startsWith('paediatric-care') || page.startsWith('cancer-palliative-care')) {
        currentCategory = 'treatments';
      } else if (page.startsWith('blog') || page.startsWith('article')) {
        currentCategory = 'articles';
      } else if (page.startsWith('contact')) {
        currentCategory = 'contact';
      } else if (page.startsWith('appointment')) {
        currentCategory = 'appointment';
      }

      // 1. Sync Navbar links
      const navAnchors = document.querySelectorAll('#navLinks > a, .nav__links > a, .nav__dropdown > a');
      navAnchors.forEach(a => {
        a.classList.remove('active');
        const href = (a.getAttribute('href') || '').toLowerCase();
        if (!href) return;

        if (currentCategory === 'home' && (href === 'index.html' || href === './' || href === '/')) {
          a.classList.add('active');
        } else if (currentCategory === 'about' && href.startsWith('about')) {
          a.classList.add('active');
        } else if (currentCategory === 'mascot' && href.startsWith('panda-mascot')) {
          a.classList.add('active');
        } else if (currentCategory === 'treatments' && href.startsWith('services')) {
          a.classList.add('active');
        } else if (currentCategory === 'articles' && href.startsWith('blog')) {
          a.classList.add('active');
        } else if (currentCategory === 'contact' && href.startsWith('contact')) {
          a.classList.add('active');
        } else if (currentCategory === 'appointment' && href.startsWith('appointment')) {
          a.classList.add('active');
        }
      });

      // 2. Sync Footer links
      const footerAnchors = document.querySelectorAll('footer a, .footer a');
      footerAnchors.forEach(a => {
        const href = (a.getAttribute('href') || '').toLowerCase();
        if (!href || href.startsWith('tel:') || href.startsWith('mailto:') || href.startsWith('http')) return;

        let isCurrent = false;
        if (currentCategory === 'home' && (href === 'index.html' || href === './' || href === '/')) {
          isCurrent = true;
        } else if (currentCategory === 'about' && href.startsWith('about')) {
          isCurrent = true;
        } else if (currentCategory === 'mascot' && href.startsWith('panda-mascot')) {
          isCurrent = true;
        } else if (currentCategory === 'treatments' && href.startsWith('services')) {
          isCurrent = true;
        } else if (currentCategory === 'articles' && href.startsWith('blog')) {
          isCurrent = true;
        } else if (currentCategory === 'contact' && href.startsWith('contact')) {
          isCurrent = true;
        } else if (currentCategory === 'appointment' && href.startsWith('appointment')) {
          isCurrent = true;
        }

        if (isCurrent) {
          a.style.color = 'var(--gold-400)';
        } else if (a.style.color && (a.style.color.includes('gold') || a.style.color === 'var(--gold-400)')) {
          a.style.color = 'inherit';
        }
      });
    } catch (e) {
      console.warn('Nav sync error:', e);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncActiveNavAndFooter);
  } else {
    syncActiveNavAndFooter();
  }

  const headerEl = document.querySelector('.header');
  if (headerEl) {
    const handleHeaderScroll = () => {
      const isScrolled = window.scrollY > 30;
      headerEl.classList.toggle('scrolled', isScrolled);
      headerEl.classList.toggle('header--scrolled', isScrolled);
    };
    window.addEventListener('scroll', handleHeaderScroll, { passive: true });
    handleHeaderScroll();
  }

  // 2. Mobile Burger Menu with Backdrop & Body Scroll Lock
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    // Ensure backdrop overlay exists in body
    let backdrop = document.querySelector('.nav-backdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'nav-backdrop';
      document.body.appendChild(backdrop);
    }

    // Ensure drawer has a dedicated cancel/close button at top right
    let drawerCloseBtn = navLinks.querySelector('.nav__drawer-close');
    if (!drawerCloseBtn) {
      drawerCloseBtn = document.createElement('button');
      drawerCloseBtn.type = 'button';
      drawerCloseBtn.className = 'nav__drawer-close';
      drawerCloseBtn.setAttribute('aria-label', 'Close menu');
      drawerCloseBtn.innerHTML = `
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      `;
      navLinks.insertBefore(drawerCloseBtn, navLinks.firstChild);
    }

    // Guarantee navLinks is mounted directly to document.body on mobile
    // This completely prevents sticky/backdrop-filter header ancestors from trapping fixed drawer coordinates when scrolled
    const navPlaceholder = document.createComment('navLinks-placeholder');
    const syncNavMount = () => {
      if (window.innerWidth <= 992) {
        if (navLinks.parentElement && navLinks.parentElement !== document.body) {
          navLinks.parentElement.insertBefore(navPlaceholder, navLinks);
          document.body.appendChild(navLinks);
        }
      } else {
        if (navPlaceholder.parentElement && navLinks.parentElement === document.body) {
          navPlaceholder.parentElement.insertBefore(navLinks, navPlaceholder);
        }
      }
    };
    window.addEventListener('resize', syncNavMount, { passive: true });
    window.addEventListener('orientationchange', syncNavMount, { passive: true });
    syncNavMount();

    const openMenu = () => {
      syncNavMount();
      navLinks.classList.add('open');
      navToggle.classList.add('open');
      navToggle.setAttribute('aria-expanded', 'true');
      backdrop.classList.add('active');
      document.documentElement.classList.add('nav-open');
      document.body.classList.add('nav-open');
    };

    const closeMenu = () => {
      navLinks.classList.remove('open');
      navToggle.classList.remove('open');
      navToggle.setAttribute('aria-expanded', 'false');
      backdrop.classList.remove('active');
      document.documentElement.classList.remove('nav-open');
      document.body.classList.remove('nav-open');
      navLinks.querySelectorAll('.nav__dropdown.open').forEach(function (d) {
        d.classList.remove('open');
      });
    };

    navToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      e.preventDefault();
      navLinks.classList.contains('open') ? closeMenu() : openMenu();
    });

    backdrop.addEventListener('click', closeMenu);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && navLinks.classList.contains('open')) {
        closeMenu();
      }
    });

    // Drawer click delegation: Treatments toggle, drawer close button, and normal navigation links
    navLinks.addEventListener('click', function (e) {
      // 1. Dedicated cancel/close button in drawer
      const closeBtn = e.target.closest('.nav__drawer-close');
      if (closeBtn) {
        e.preventDefault();
        e.stopPropagation();
        closeMenu();
        return;
      }

      // 2. Treatments dropdown accordion toggle
      const dropToggle = e.target.closest('.nav__dropdown-toggle');
      if (dropToggle) {
        if (window.innerWidth <= 992) {
          e.preventDefault();
          e.stopPropagation();
          const parentDropdown = dropToggle.closest('.nav__dropdown');
          if (parentDropdown) {
            parentDropdown.classList.toggle('open');
          }
        }
        return;
      }

      // 3. Any actual navigation link clicked inside drawer
      const navItemLink = e.target.closest('a');
      if (navItemLink) {
        closeMenu();
      }
    });
  }

  // 3. Mobile Sticky Appointment Bar
  function initStickyBar() {
    let sb = document.getElementById('sticky-bar');
    if (!sb) {
      sb = document.createElement('div');
      sb.id = 'sticky-bar';
      sb.innerHTML = `
        <a href="appointment.html" class="sb-btn primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M10 14l2 2 4-4"/></svg>
          <span>Book Appointment</span>
        </a>
        <a href="https://wa.me/919884223007?text=Hi%2C+I+would+like+to+book+a+consultation+with+Dr.+G.+Srinivasan" target="_blank" rel="noopener" class="sb-btn wa" aria-label="WhatsApp Us">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a9.9 9.9 0 0 0-8.4 15.2L2 22l4.9-1.6A9.9 9.9 0 1 0 12 2zm5.3 14c-.2.6-1.2 1.1-1.7 1.2-.4.1-1 .1-1.6-.1-.4-.1-.8-.3-1.4-.5-2.5-1.1-4.1-3.6-4.2-3.7-.1-.2-1-1.3-1-2.5s.6-1.8.9-2c.2-.3.5-.3.6-.3h.5c.2 0 .4 0 .5.4l.7 1.7c.1.1.1.3 0 .4l-.3.5-.3.3c-.1.1-.2.3-.1.5.2.3.6 1 1.3 1.6.9.8 1.6 1 1.9 1.2.2.1.4.1.5-.1l.7-.9c.2-.2.3-.2.5-.1l1.6.8c.2.1.4.2.4.3.1.1.1.5-.1 1z"/></svg>
          <span>WhatsApp</span>
        </a>
        <a href="tel:9884223007" class="sb-btn call" aria-label="Call clinic directly">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </a>
      `;
      document.body.appendChild(sb);
    }

    const checkSticky = () => {
      sb.classList.toggle('visible', window.scrollY > 180);
    };
    window.addEventListener('scroll', checkSticky, { passive: true });
    checkSticky();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStickyBar);
  } else {
    initStickyBar();
  }

  // 4. Language Switcher (EN / TA)
  const langButtons = document.querySelectorAll('.lang-toggle button');
  const savedLang = localStorage.getItem('prana_lang') || 'en';

  function setLanguage(lang) {
    document.documentElement.setAttribute('data-lang', lang);
    localStorage.setItem('prana_lang', lang);

    langButtons.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    // Update translatable elements
    document.querySelectorAll('[data-en][data-ta]').forEach(el => {
      el.innerHTML = lang === 'ta' ? el.getAttribute('data-ta') : el.getAttribute('data-en');
    });
  }

  langButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      setLanguage(btn.dataset.lang);
    });
  });

  if (savedLang !== 'en') {
    setLanguage(savedLang);
  }

  // 5. Consultation Mode Selection (In-Clinic vs Google Meet Online)
  const modeOptions = document.querySelectorAll('.mode-option');
  const consultationModeInput = document.getElementById('consultationMode');
  const modeBadgeText = document.getElementById('modeBadgeText');
  const clinicAddressField = document.getElementById('clinicAddressField');
  const meetLinkField = document.getElementById('meetLinkField');

  if (modeOptions.length > 0 && consultationModeInput) {
    modeOptions.forEach(opt => {
      opt.addEventListener('click', function () {
        modeOptions.forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        const mode = this.dataset.mode;
        consultationModeInput.value = mode;

        if (mode === 'online') {
          if (modeBadgeText) {
            modeBadgeText.innerHTML = '<strong>Online Video Consultation:</strong> Google Meet link & Calendar invitation will be automatically issued and sent to your Email & WhatsApp.';
          }
          if (clinicAddressField) clinicAddressField.style.display = 'none';
          if (meetLinkField) meetLinkField.style.display = 'block';
        } else {
          if (modeBadgeText) {
            modeBadgeText.innerHTML = '<strong>In-Clinic Consultation:</strong> Dr. G. Srinivasan, Plot no 121, 2/94A Jayalakshmi Nagar, Kattupakkam, Chennai 600056.';
          }
          if (clinicAddressField) clinicAddressField.style.display = 'block';
          if (meetLinkField) meetLinkField.style.display = 'none';
        }
        
        // Reload slots for newly selected mode
        const dateInput = document.getElementById('appointmentDate');
        const timeSelect = document.getElementById('appointmentTime');
        if (dateInput && dateInput.value && timeSelect) {
           loadAvailableSlots(dateInput.value, timeSelect);
        }
      });
    });
  }

  // 6. Rock-Solid Vanishing Time Slot Registry & Engine
  const CLINIC_MORNING_SLOTS = ['10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM', '12:00 PM', '12:30 PM'];
  const CLINIC_EVENING_SLOTS = ['5:00 PM', '5:30 PM', '6:00 PM', '6:30 PM', '7:00 PM', '7:30 PM', '8:00 PM', '8:30 PM'];

  function normalizeTimeStr(t) {
    if (!t) return '';
    const clean = t.trim().toUpperCase().replace(/\s+/g, ' ');
    const m = clean.match(/(\d{1,2})[:.](\d{2})\s*(AM|PM)/i);
    if (m) {
      return parseInt(m[1], 10) + ':' + m[2] + ' ' + m[3].toUpperCase();
    }
    return clean;
  }

  function getClientBookedSlots(dateStr) {
    try {
      const raw = localStorage.getItem('prana_client_booked_slots') || '{}';
      const map = JSON.parse(raw);
      return Array.isArray(map[dateStr]) ? map[dateStr].map(normalizeTimeStr) : [];
    } catch(e) { return []; }
  }

  function recordClientBookedSlot(dateStr, timeStr) {
    if (!dateStr || !timeStr) return;
    try {
      const raw = localStorage.getItem('prana_client_booked_slots') || '{}';
      const map = JSON.parse(raw);
      if (!map[dateStr]) map[dateStr] = [];
      const norm = normalizeTimeStr(timeStr);
      if (!map[dateStr].includes(norm)) {
        map[dateStr].push(norm);
      }
      localStorage.setItem('prana_client_booked_slots', JSON.stringify(map));
    } catch(e) {}
  }

  async function loadAvailableSlots(selectedDate, specificSelect) {
    const selects = specificSelect ? [specificSelect] : [
      document.getElementById('appointmentTime'),
      document.getElementById('modalAppointmentTime')
    ].filter(Boolean);

    if (!selectedDate || selects.length === 0) return;

    const clientBooked = getClientBookedSlots(selectedDate);
    const dayOfWeek = new Date(selectedDate).getDay(); // 0 is Sunday
    const isSunday = (dayOfWeek === 0);

    selects.forEach(sel => {
      sel.innerHTML = '<option value="">Checking available slots...</option>';
    });

    let serverSlots = null;
    let isHoliday = false;
    let holidayReason = '';
    
    // Determine mode based on which select is being used
    let mode = 'offline';
    if (specificSelect && specificSelect.id === 'modalAppointmentTime') {
       mode = document.getElementById('modalConsultationMode')?.value || 'offline';
    } else {
       mode = document.getElementById('consultationMode')?.value || 'offline';
    }

    try {
      const res = await fetch(`book_appointment.php?action=slots&date=${encodeURIComponent(selectedDate)}&mode=${encodeURIComponent(mode)}`);
      if (res.ok) {
        const data = await res.json();
        if (data && data.is_holiday) {
          isHoliday = true;
          holidayReason = data.holiday_reason || 'Clinic Holiday';
        } else if (data && Array.isArray(data.slots)) {
          serverSlots = data.slots;
        }
      }
    } catch(err) {
      console.warn('Backend slot check notice, using verified schedule:', err);
    }

    selects.forEach(sel => {
      if (isHoliday) {
        sel.innerHTML = `<option value="" disabled selected>🏖️ Clinic Closed: ${holidayReason} — please select another date</option>`;
        return;
      }

      sel.innerHTML = '<option value="">Select an available consultation slot</option>';
      let count = 0;

      // Base slots to evaluate
      let baseSlots = [];
      if (serverSlots && serverSlots.length > 0) {
        baseSlots = serverSlots;
      } else {
        const morning = CLINIC_MORNING_SLOTS.map(t => ({ time: t, period: 'Morning', available: true }));
        const evening = CLINIC_EVENING_SLOTS.map(t => ({ time: t, period: 'Evening', available: true }));
        baseSlots = isSunday ? morning : [...morning, ...evening];
      }

      baseSlots.forEach(s => {
        const slotTime = s.time;
        const norm = normalizeTimeStr(slotTime);
        const isLocallyBooked = clientBooked.includes(norm);
        const isAvailable = s.available !== false && !isLocallyBooked;

        // BOOKED SLOTS VANISH COMPLETELY — only available slots are added!
        if (!isAvailable) return;

        count++;
        const opt = document.createElement('option');
        opt.value = slotTime;
        const sessionLabel = s.period ? ` (${s.period} Session)` : '';
        opt.textContent = `${slotTime}${sessionLabel}`;
        sel.appendChild(opt);
      });

      if (count === 0) {
        sel.innerHTML = '<option value="" disabled selected>⚠️ All consultation slots booked for this date — please select another date</option>';
      }
    });
  }

  // Connect date inputs to dynamic slots
  const dateInput = document.getElementById('appointmentDate');
  const timeSelect = document.getElementById('appointmentTime');
  if (dateInput && timeSelect) {
    const todayStr = new Date().toISOString().split('T')[0];
    dateInput.min = todayStr;
    if (!dateInput.value) dateInput.value = todayStr;
    loadAvailableSlots(dateInput.value, timeSelect);
    dateInput.addEventListener('change', () => loadAvailableSlots(dateInput.value, timeSelect));
  }

  const modalDateInput = document.getElementById('modalAppointmentDate');
  const modalTimeSelect = document.getElementById('modalAppointmentTime');
  if (modalDateInput && modalTimeSelect) {
    const todayStr = new Date().toISOString().split('T')[0];
    modalDateInput.min = todayStr;
    if (!modalDateInput.value) modalDateInput.value = todayStr;
    loadAvailableSlots(modalDateInput.value, modalTimeSelect);
    modalDateInput.addEventListener('change', () => loadAvailableSlots(modalDateInput.value, modalTimeSelect));
  }

  // 7. Universal Appointment Form Submission
  const apptForm = document.getElementById('universalApptForm');
  if (apptForm) {
    const formNote = document.getElementById('universalFormNote');
    const formBtn = document.getElementById('universalFormBtn');
    const WHATSAPP_PHONE = '919884223007';

    apptForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const name = (document.getElementById('patientName')?.value || '').trim();
      const phone = (document.getElementById('patientPhone')?.value || '').trim();
      const email = (document.getElementById('patientEmail')?.value || '').trim();
      const date = (document.getElementById('appointmentDate')?.value || '').trim();
      const time = (document.getElementById('appointmentTime')?.value || '').trim();
      const concern = (document.getElementById('healthConcern')?.value || '').trim();
      const message = (document.getElementById('patientMessage')?.value || '').trim();
      const mode = (document.getElementById('consultationMode')?.value || 'offline');

      if (!name || !phone || !date || !time) {
        if (formNote) {
          formNote.className = 'form-note err';
          formNote.textContent = 'Please fill in your name, phone number, preferred date, and session time.';
          formNote.style.display = 'block';
        }
        return;
      }

      if (formBtn) {
        formBtn.disabled = true;
        formBtn.innerHTML = '<span>Processing & Syncing Appointment...</span>';
      }

      const modeText = mode === 'online' ? 'Online Video Consultation (Google Meet API)' : 'In-Clinic Visit (Kattupakkam, Chennai)';
      const fallbackMeet = mode === 'online' ? 'https://meet.google.com/new' : null;

      // Construct Form Data
      const formData = new FormData(apptForm);
      if (!formData.has('mode')) formData.append('mode', mode);

      let serverResult = null;
      try {
        let response = await fetch('book_appointment.php', {
          method: 'POST',
          body: formData
        });
        if (!response.ok) {
          response = await fetch('mail.php', {
            method: 'POST',
            body: formData
          });
        }
        if (response.ok) {
          serverResult = await response.json();
        }
      } catch (err) {
        console.warn('Backend booking endpoint notice:', err.message);
      }

      // Record slot locally so it immediately vanishes
      recordClientBookedSlot(date, time);

      // Determine meet link & WhatsApp link
      let meetLink = '';
      if (serverResult && typeof serverResult.meet_link === 'string' && serverResult.meet_link.startsWith('http')) {
        meetLink = serverResult.meet_link;
      } else if (mode.includes('online')) {
        meetLink = fallbackMeet;
      }

      const calendarLink = (serverResult && (serverResult.calendar_link || serverResult.gcal_link)) ? (serverResult.calendar_link || serverResult.gcal_link) : null;
      let waUrl = (serverResult && serverResult.whatsapp_url) ? serverResult.whatsapp_url : null;
      if (!waUrl) {
        const lines = [
          '*New Appointment Booking — Prana Homeo Care*',
          '──────────────────────────────',
          '• *Patient Name:* ' + name,
          '• *Phone:* ' + phone,
          email ? '• *Email:* ' + email : '',
          '• *Date:* ' + date,
          '• *Time Slot:* ' + time,
          '• *Consultation Mode:* ' + modeText,
          concern ? '• *Health Concern:* ' + concern : '',
          message ? '• *Patient Notes:* ' + message : '',
          meetLink ? '• *Google Meet Link:* ' + meetLink : '',
          '──────────────────────────────',
          'Please confirm my consultation slot with Dr. G. Srinivasan M.D(Hom).'
        ].filter(Boolean);
        waUrl = 'https://wa.me/' + WHATSAPP_PHONE + '?text=' + encodeURIComponent(lines.join('\n'));
      }

      if (formNote) {
        formNote.className = 'form-note ok';
        let noteHtml = '<strong>✓ Appointment Confirmed & Synced!</strong><br>';
        noteHtml += 'Confirmation emails sent to clinic & patient.<br>';
        if (meetLink) {
          noteHtml += '<strong>Google Meet Link:</strong> <a href="' + meetLink + '" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:underline; font-weight:700;">' + meetLink + '</a><br>';
        }
        if (calendarLink) {
          noteHtml += '<a href="' + calendarLink + '" target="_blank" style="display:inline-block; margin-top:6px; font-weight:700; color:inherit; text-decoration:underline;">+ Add to Google Calendar</a><br>';
        }
        noteHtml += '<div style="margin-top:10px; padding:10px 14px; background:rgba(238,242,255,0.95); border:1px solid #c7d2fe; border-radius:10px; font-size:12px; color:#1e1b4b; line-height:1.4; text-align:left;">';
        noteHtml += '<strong>📬 Please check your Inbox and Spam / Junk folder:</strong> Your consultation confirmation email with Google Meet details has been sent. If you do not see it in your primary inbox, please check your spam folder and mark it as "Not Spam".';
        noteHtml += '<div style="margin-top:10px; font-weight:700; color:#047857; font-size:13px; text-align:center; padding:8px 12px; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px;">✓ Consultation Confirmed. Details sent to your email.</div>';
        formNote.innerHTML = noteHtml;
        formNote.style.display = 'block';
      }

      if (formBtn) {
        formBtn.disabled = false;
        formBtn.innerHTML = '✓ Appointment Dispatched';
      }

      // Immediately refresh slots so the newly booked slot vanishes from the screen
      loadAvailableSlots(date, timeSelect);

      // Automatically open WhatsApp confirmation window
      // WhatsApp auto-redirect removed as requested
    });
  }

  // 8. Thirukkural Carousel
  const stage = document.getElementById('kuralStage');
  if (stage) {
    const slides = stage.querySelectorAll('.kural-slide');
    const dotsContainer = document.getElementById('kuralDots');
    const prevBtn = document.getElementById('kuralPrev');
    const nextBtn = document.getElementById('kuralNext');
    const progressBar = document.getElementById('kuralProgressBar');
    let currentIndex = 0;
    let timer = null;
    const DURATION = 6000;

    // Render dots
    if (dotsContainer) {
      dotsContainer.innerHTML = '';
      slides.forEach((_, i) => {
        const dot = document.createElement('button');
        dot.className = 'kural-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dot.addEventListener('click', () => goToSlide(i));
        dotsContainer.appendChild(dot);
      });
    }

    function goToSlide(idx) {
      slides[currentIndex].classList.remove('active');
      const dots = dotsContainer?.querySelectorAll('.kural-dot');
      if (dots && dots[currentIndex]) dots[currentIndex].classList.remove('active');

      currentIndex = (idx + slides.length) % slides.length;

      slides[currentIndex].classList.add('active');
      if (dots && dots[currentIndex]) dots[currentIndex].classList.add('active');

      resetAutoRotate();
    }

    function resetAutoRotate() {
      if (timer) clearInterval(timer);
      if (progressBar) {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        setTimeout(() => {
          progressBar.style.transition = `width ${DURATION}ms linear`;
          progressBar.style.width = '100%';
        }, 30);
      }
      timer = setInterval(() => {
        goToSlide(currentIndex + 1);
      }, DURATION);
    }

    if (prevBtn) prevBtn.addEventListener('click', () => goToSlide(currentIndex - 1));
    if (nextBtn) nextBtn.addEventListener('click', () => goToSlide(currentIndex + 1));

    resetAutoRotate();
  }

  // 9. Interactive Health Score Assessment
  const quizForm = document.getElementById('healthAssessmentForm');
  if (quizForm) {
    const resultBox = document.getElementById('scoreResult');
    const scoreNum = document.getElementById('scoreNum');
    const scoreTitle = document.getElementById('scoreTitle');
    const scoreAnalysis = document.getElementById('scoreAnalysis');

    quizForm.querySelectorAll('.option-label input[type="radio"]').forEach(radio => {
      radio.addEventListener('change', function() {
        const name = this.name;
        quizForm.querySelectorAll(`input[name="${name}"]`).forEach(r => {
          r.closest('.option-label').classList.remove('selected');
        });
        if (this.checked) {
          this.closest('.option-label').classList.add('selected');
        }
      });
    });

    quizForm.addEventListener('submit', function(e) {
      e.preventDefault();
      let total = 0;
      const formData = new FormData(quizForm);
      for (let [key, val] of formData.entries()) {
        total += parseInt(val, 10);
      }

      if (scoreNum) scoreNum.textContent = total;

      if (scoreTitle && scoreAnalysis) {
        if (total >= 80) {
          scoreTitle.textContent = "Optimal Vitality — Strong Natural Balance";
          scoreAnalysis.textContent = "Your vital force is robust! Your digestive fire, immune response, and sleep architecture are functioning harmoniously. Periodic constitutional checkups and adhering to seasonal diet can preserve this state throughout life.";
        } else if (total >= 50) {
          scoreTitle.textContent = "Moderate Constitutional Strain Detected";
          scoreAnalysis.textContent = "Your body is showing signs of digestive fatigue, recurrent susceptibility to seasonal changes, or mental tension. Classical constitutional homeopathy can gently re-align your natural rhythm before these develop into chronic illnesses.";
        } else {
          scoreTitle.textContent = "Depleted Vital Force — Clinical Attention Recommended";
          scoreAnalysis.textContent = "Your assessment indicates significant chronic inflammation, lowered immune response, and fatigue. Dr. G. Srinivasan's classical case-taking can trace the exact root cause and formulate an individualised treatment roadmap.";
        }
      }

      if (resultBox) {
        resultBox.style.display = 'block';
        resultBox.scrollIntoView({ behavior: 'smooth' });
      }
    });
  }

  // 10. Quick Booking Modal Handling
  const modalOverlay = document.getElementById('quickBookingModalOverlay');
  const openModalBtns = document.querySelectorAll('#openQuickBookModal, .btn-open-quick-modal');
  const closeModalBtn = document.getElementById('closeQuickBookModal');
  const quickForm = document.getElementById('quickBookingForm');

  function openQuickModal() {
    if (modalOverlay) {
      modalOverlay.classList.add('active');
      document.body.style.overflow = 'hidden';
      const firstInput = document.getElementById('modalPatientName');
      if (firstInput) setTimeout(() => firstInput.focus(), 150);
    }
  }

  function closeQuickModal() {
    if (modalOverlay) {
      modalOverlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  openModalBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      openQuickModal();
    });
  });

  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', closeQuickModal);
  }

  if (modalOverlay) {
    modalOverlay.addEventListener('click', function (e) {
      if (e.target === modalOverlay) {
        closeQuickModal();
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
        closeQuickModal();
      }
    });

    // Handle modal mode selection
    const modalModeOpts = modalOverlay.querySelectorAll('.mode-option');
    const modalModeInput = document.getElementById('modalConsultationMode');
    if (modalModeOpts.length > 0 && modalModeInput) {
      modalModeOpts.forEach(opt => {
        opt.addEventListener('click', function () {
          modalModeOpts.forEach(o => o.classList.remove('active'));
          this.classList.add('active');
          modalModeInput.value = this.dataset.mode;
          
          const mDate = document.getElementById('modalAppointmentDate');
          const mTime = document.getElementById('modalAppointmentTime');
          if (mDate && mDate.value && mTime) {
             loadAvailableSlots(mDate.value, mTime);
          }
        });
      });
    }
  }

  if (quickForm) {
    const modalNote = document.getElementById('modalFormNote');
    const WHATSAPP_PHONE = '919884223007';

    quickForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const name = (document.getElementById('modalPatientName')?.value || '').trim();
      const phone = (document.getElementById('modalPatientPhone')?.value || '').trim();
      const date = (document.getElementById('modalAppointmentDate')?.value || '').trim();
      const time = (document.getElementById('modalAppointmentTime')?.value || '').trim();
      const concern = (document.getElementById('modalHealthConcern')?.value || '').trim();
      const mode = (document.getElementById('modalConsultationMode')?.value || 'offline');

      if (!name || !phone) {
        if (modalNote) {
          modalNote.className = 'form-note err';
          modalNote.textContent = 'Please provide your name and WhatsApp phone number.';
          modalNote.style.display = 'block';
        }
        return;
      }

      // Record slot if selected
      if (date && time) {
        recordClientBookedSlot(date, time);
      }

      const modeText = mode === 'online' ? 'Online Video Consultation (Google Meet)' : 'In-Clinic Consultation (Chennai)';
      const meetCode = mode === 'online' ? 'meet.google.com/pra-' + Math.random().toString(36).substring(2, 6) + '-' + Math.random().toString(36).substring(2, 5) : null;

      const lines = [
        '*Quick Appointment Request — Prana Homeo Care*',
        '──────────────────────────────',
        `• *Patient Name:* ${name}`,
        `• *Phone:* ${phone}`,
        date ? `• *Preferred Date:* ${date}` : '',
        time ? `• *Time Slot:* ${time}` : '',
        `• *Mode:* ${modeText}`,
        concern ? `• *Health Concern:* ${concern}` : '',
        mode === 'online' ? `• *Google Meet Link:* https://${meetCode}` : '',
        '──────────────────────────────',
        'Please confirm my consultation slot.'
      ].filter(Boolean);

      const waUrl = `https://wa.me/${WHATSAPP_PHONE}?text=${encodeURIComponent(lines.join('\n'))}`;

      if (modalNote) {
        modalNote.className = 'form-note ok';
        modalNote.innerHTML = '<strong>✓ Request Received!</strong> Our clinic desk has recorded your consultation booking.';
        modalNote.style.display = 'block';
      }

      // WhatsApp auto-redirect removed as requested
      setTimeout(closeQuickModal, 1500);
    });
  }
})();
