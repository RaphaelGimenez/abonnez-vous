// Subscription page billing period toggle
document.addEventListener('DOMContentLoaded', () => {
	const monthlyBtn = document.getElementById('monthlyBtn');
	const yearlyBtn = document.getElementById('yearlyBtn');

	if (!monthlyBtn || !yearlyBtn) return;

	const monthlyPrices = document.querySelectorAll('.price-monthly');
	const yearlyPrices = document.querySelectorAll('.price-yearly');
	const billingPeriodInputs = document.querySelectorAll('.billing-period-input');

	monthlyBtn.addEventListener('click', () => {
		// Update button styles and aria-selected attributes
		monthlyBtn.classList.add('bg-white', 'shadow-sm', 'text-cyan-600');
		monthlyBtn.classList.remove('text-gray-600');
		monthlyBtn.setAttribute('aria-selected', 'true');
		yearlyBtn.classList.remove('bg-white', 'shadow-sm', 'text-cyan-600');
		yearlyBtn.classList.add('text-gray-600');
		yearlyBtn.setAttribute('aria-selected', 'false');

		// Show monthly prices, hide yearly prices
		monthlyPrices.forEach(el => el.classList.remove('hidden'));
		yearlyPrices.forEach(el => el.classList.add('hidden'));
		// Set billing period input values
		billingPeriodInputs.forEach(input => input.value = 'monthly');
	});

	yearlyBtn.addEventListener('click', () => {
		// Update button styles and aria-selected attributes
		yearlyBtn.classList.add('bg-white', 'shadow-sm', 'text-cyan-600');
		yearlyBtn.classList.remove('text-gray-600');
		yearlyBtn.setAttribute('aria-selected', 'true');
		monthlyBtn.classList.remove('bg-white', 'shadow-sm', 'text-cyan-600');
		monthlyBtn.classList.add('text-gray-600');
		monthlyBtn.setAttribute('aria-selected', 'false');

		// Show yearly prices, hide monthly prices
		yearlyPrices.forEach(el => el.classList.remove('hidden'));
		monthlyPrices.forEach(el => el.classList.add('hidden'));
		// Set billing period input values
		billingPeriodInputs.forEach(input => input.value = 'yearly');
	});
});
