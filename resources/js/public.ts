const menuToggle = document.querySelector<HTMLButtonElement>('.menu-toggle');
const mobileMenu = document.querySelector<HTMLElement>('#mobile-menu');
function closeMenu(returnFocus = false) {
  if (menuToggle && mobileMenu) { menuToggle.setAttribute('aria-expanded', 'false'); mobileMenu.hidden = true; if (returnFocus) menuToggle.focus(); }
}
menuToggle?.addEventListener('click', () => {
  if (!mobileMenu) return;
  const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
  mobileMenu.style.setProperty('--mobile-menu-top', `${document.querySelector('.site-header')?.getBoundingClientRect().bottom ?? 80}px`);
  menuToggle.setAttribute('aria-expanded', String(!expanded)); mobileMenu.hidden = expanded;
});
document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') closeMenu(true); });
document.addEventListener('click', event => { if (event.target instanceof Node && !document.querySelector('.site-header')?.contains(event.target)) closeMenu(); });
document.addEventListener('focusin', event => { if (event.target instanceof Node && !document.querySelector('.site-header')?.contains(event.target)) closeMenu(); });
mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeMenu()));
window.matchMedia('(min-width: 1100px)').addEventListener('change', () => closeMenu());

const lightbox = document.querySelector<HTMLDialogElement>('#lightbox');
document.querySelectorAll<HTMLButtonElement>('[data-lightbox]').forEach((button) => {
  button.addEventListener('click', () => {
    const image = lightbox?.querySelector('img');
    if (!lightbox || !image) return;
    image.src = button.dataset.lightbox ?? ''; image.alt = button.dataset.alt ?? '';
    const caption = lightbox.querySelector('p'); if (caption) caption.textContent = image.alt;
    lightbox.showModal();
  });
});
lightbox?.querySelector('.lightbox-close')?.addEventListener('click', () => lightbox.close());
lightbox?.addEventListener('click', (event) => { if (event.target === lightbox) lightbox.close(); });

document.querySelectorAll<HTMLInputElement>('.comparison-range').forEach((range) => {
  range.addEventListener('input', () => {
    const figure = range.closest<HTMLElement>('.comparison');
    figure?.style.setProperty('--comparison', `${range.value}%`);
  });
});

type BranchOptions = Record<string, { services: number[]; professionals: number[]; hours: { day: string; open: string; close: string; closed: boolean }[] }>;
const data = document.getElementById('branch-options');
const branchSelect = document.querySelector<HTMLSelectElement>('#branch_id');
if (data && branchSelect) {
  const options: BranchOptions = JSON.parse(data.textContent ?? '{}');
  function updateBranch() {
    const branch = options[branchSelect!.value]; if (!branch) return;
    for (const [field, ids] of [['service_id', branch.services], ['professional_id', branch.professionals]] as const) {
      const select = document.getElementById(field) as HTMLSelectElement | null;
      select?.querySelectorAll('option').forEach((option) => {
        if (!option.value) return;
        option.disabled = !ids.includes(Number(option.value)); option.hidden = option.disabled;
        if (option.disabled && option.selected) select.value = '';
      });
    }
    const date = document.querySelector<HTMLInputElement>('#preferred_date');
    const time = document.querySelector<HTMLInputElement>('#preferred_time');
    const availability = document.querySelector<HTMLElement>('#branch-availability');
    time?.removeAttribute('min'); time?.removeAttribute('max');
    date?.setCustomValidity('');
    if (availability) availability.textContent = '';
    if (date?.value && time) {
      const day = new Date(`${date.value}T12:00:00`).toLocaleDateString('en-US', { weekday: 'long' });
      const hours = branch.hours?.find((h) => h.day === day);
      if (hours && !hours.closed) {
        time.min = hours.open; time.max = hours.close;
        if (availability) availability.textContent = `${availability.dataset.hoursLabel} ${hours.open}–${hours.close}`;
      } else if (hours?.closed && availability) {
        availability.textContent = availability.dataset.closedLabel ?? '';
        date.setCustomValidity(availability.textContent);
      }
    }
  }
  branchSelect.addEventListener('change', updateBranch);
  document.querySelector('#preferred_date')?.addEventListener('change', updateBranch); updateBranch();
}
document.getElementById('form-errors')?.focus();
document.querySelectorAll<HTMLFormElement>('form').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const submit = form.querySelector<HTMLButtonElement>('.submit-button');
    if (submit && form.checkValidity() && !previewToken && !event.defaultPrevented) {
      submit.dataset.originalMarkup = submit.innerHTML;
      submit.disabled = true; submit.setAttribute('aria-busy', 'true');
      if (submit.dataset.loadingLabel) submit.textContent = submit.dataset.loadingLabel;
    }
  });
});

window.addEventListener('pageshow', () => {
  document.querySelectorAll<HTMLButtonElement>('.submit-button[data-original-markup]').forEach(button => {
    button.disabled = false; button.removeAttribute('aria-busy');
    button.innerHTML = button.dataset.originalMarkup ?? ''; delete button.dataset.originalMarkup;
  });
});

const preferences = document.body.dataset;
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
if (preferences.motionEnabled === 'true' && preferences.motionScrollReveal === 'true' && !reducedMotion.matches && 'IntersectionObserver' in window) {
  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('reveal-enter'); observer.unobserve(entry.target); }
    });
  }, { threshold: .12 });
  document.querySelectorAll('.section-header,.about-copy,.technology-card,.appointment-banner,.branch-page-card').forEach(element => observer.observe(element));
  reducedMotion.addEventListener('change', event => { if (event.matches) observer.disconnect(); });
}
const header = document.querySelector('.site-header');
if (header && 'IntersectionObserver' in window) {
  const sentinel = document.createElement('span'); sentinel.setAttribute('aria-hidden','true'); header.before(sentinel);
  new IntersectionObserver(([entry]) => header.classList.toggle('scrolled', !entry.isIntersecting)).observe(sentinel);
}
const previewToken = new URLSearchParams(location.search).get('appearance_preview');
if (previewToken) {
  document.querySelectorAll<HTMLAnchorElement>('a[href]').forEach(link => {
    const url = new URL(link.href);
    if (url.origin === location.origin && !url.pathname.startsWith('/admin') && !url.pathname.startsWith('/login') && !link.hash) {
      url.searchParams.set('appearance_preview', previewToken); link.href = url.href;
    }
  });
  document.querySelectorAll<HTMLFormElement>('form').forEach(form => {
    if (form.method.toLowerCase() === 'get') { const input = document.createElement('input'); input.type='hidden';input.name='appearance_preview';input.value=previewToken;form.append(input); }
    else form.addEventListener('submit', event => event.preventDefault());
  });
}
