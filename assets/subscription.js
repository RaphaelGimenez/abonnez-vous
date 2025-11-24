// Subscription page billing period toggle
document.addEventListener('DOMContentLoaded', () => {
  const monthlyBtn = document.getElementById('monthlyBtn');
  const annualBtn = document.getElementById('annualBtn');

  if (!monthlyBtn || !annualBtn) return;

  const monthlyPrices = document.querySelectorAll('.price-monthly');
  const annualPrices = document.querySelectorAll('.price-annual');
  const billingPeriodInputs = document.querySelectorAll('.billing-period-input');

  monthlyBtn.addEventListener('click', () => {
    // Update button styles and aria-selected attributes
    monthlyBtn.classList.add('bg-white', 'shadow-sm', 'text-cyan-600');
    monthlyBtn.classList.remove('text-gray-600');
    monthlyBtn.setAttribute('aria-selected', 'true');
    annualBtn.classList.remove('bg-white', 'shadow-sm', 'text-cyan-600');
    annualBtn.classList.add('text-gray-600');
    annualBtn.setAttribute('aria-selected', 'false');

    // Show monthly prices, hide annual prices
    monthlyPrices.forEach(el => el.classList.remove('hidden'));
    annualPrices.forEach(el => el.classList.add('hidden'));
    // Set billing period input values
    billingPeriodInputs.forEach(input => input.value = 'monthly');
  });

  annualBtn.addEventListener('click', () => {
    // Update button styles and aria-selected attributes
    annualBtn.classList.add('bg-white', 'shadow-sm', 'text-cyan-600');
    annualBtn.classList.remove('text-gray-600');
    annualBtn.setAttribute('aria-selected', 'true');
    monthlyBtn.classList.remove('bg-white', 'shadow-sm', 'text-cyan-600');
    monthlyBtn.classList.add('text-gray-600');
    monthlyBtn.setAttribute('aria-selected', 'false');

    // Show annual prices, hide monthly prices
    annualPrices.forEach(el => el.classList.remove('hidden'));
    monthlyPrices.forEach(el => el.classList.add('hidden'));
    // Set billing period input values
    billingPeriodInputs.forEach(input => input.value = 'annual');
  });
});
