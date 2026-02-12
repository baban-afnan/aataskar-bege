document.addEventListener('DOMContentLoaded', function () {
    // Select Elements
    const serviceField = document.getElementById('service_field');
    const fieldPrice = document.getElementById('field-price');
    const fieldDescription = document.getElementById('field-description');

    // Form Sections
    const genericInfo = document.getElementById('generic-data-info');
    const dobWizard = document.getElementById('dob-wizard');

    // Submit Buttons
    const genericSubmit = document.getElementById('generic-submit-btn');
    const dobProceed = document.getElementById('dob-proceed-btn');

    // Wizard Controls
    const wizardProgress = document.getElementById('wizard-progress');
    const stepBadge = document.getElementById('step-badge');
    const prevBtns = document.querySelectorAll('.prev-step');
    const nextBtns = document.querySelectorAll('.next-step');
    const wizardInputs = document.querySelectorAll('.dob-input');

    // --- 1. Price & Layout Updates ---
    if (serviceField) {
        serviceField.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];

            // Get price from data attribute
            const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const description = selectedOption.getAttribute('data-description') || '';
            const fieldText = selectedOption.text.toLowerCase();

            // Update UI
            fieldPrice.textContent = '₦' + price.toLocaleString('en-NG', { minimumFractionDigits: 2 });
            fieldDescription.textContent = description;

            // Reset Forms
            resetForms();

            // Check if DOB 
            if (fieldText.includes('date of birth')) {
                // Show Wizard, Hide Generic
                genericInfo.classList.add('d-none');
                genericSubmit.classList.add('d-none');

                dobWizard.classList.remove('d-none');

                // Enable Wizard Inputs
                wizardInputs.forEach(input => input.disabled = false);
            } else {
                // Show Generic, Hide Wizard
                genericInfo.classList.remove('d-none');
                genericSubmit.classList.remove('d-none');

                dobWizard.classList.add('d-none');

                // Disable Wizard Inputs to avoid validation errors
                wizardInputs.forEach(input => input.disabled = true);
            }
        });

        // Trigger change on load if value selected (e.g. user error redirect)
        if (serviceField.value) {
            serviceField.dispatchEvent(new Event('change'));
        }
    }

    // --- 2. Wizard Logic ---
    function resetForms() {
        // Reset wizard to step 1
        document.querySelectorAll('.wizard-step').forEach(step => step.classList.add('d-none'));
        document.getElementById('step-1').classList.remove('d-none');

        // Reset Progress
        if (wizardProgress) wizardProgress.style.width = '12.5%';
    }

    nextBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const currentStep = this.closest('.wizard-step');
            const nextStepId = this.getAttribute('data-next');
            const nextStep = document.getElementById(nextStepId);

            // Simple validation (can be expanded)
            const inputs = currentStep.querySelectorAll('input:not([disabled]), select:not([disabled]), textarea:not([disabled])');
            let isValid = true;

            inputs.forEach(input => {
                if (input.required && !input.value) {
                    isValid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (isValid) {
                currentStep.classList.add('d-none');
                nextStep.classList.remove('d-none');
                updateProgress(nextStepId);
            } else {
                alert('Please fill all required fields before proceeding.');
            }
        });
    });

    prevBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const currentStep = this.closest('.wizard-step');
            const prevStepId = this.getAttribute('data-prev');
            const prevStep = document.getElementById(prevStepId);

            currentStep.classList.add('d-none');
            prevStep.classList.remove('d-none');
            updateProgress(prevStepId);
        });
    });

    function updateProgress(stepId) {
        // Extract step number (e.g., 'step-3' -> 3)
        const stepNum = parseInt(stepId.split('-')[1]);
        const percent = (stepNum / 8) * 100;

        if (wizardProgress) wizardProgress.style.width = percent + '%';
        if (stepBadge) stepBadge.textContent = `Step ${stepNum} of 8`;
    }
});
