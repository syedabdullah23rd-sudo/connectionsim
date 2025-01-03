// Experience form handling
document.getElementById('experienceForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('add_experience.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if(data.success) {
            showToast('Experience added successfully');
            closeModal('addExperienceModal');
            loadExperience(); // Reload experience section
        } else {
            showToast(data.message, 'error');
        }
    } catch(err) {
        showToast('Error adding experience', 'error');
    }
});

// Currently working checkbox handler
document.getElementById('currentlyWorking').addEventListener('change', function() {
    const endDateInput = document.getElementById('endDate');
    endDateInput.disabled = this.checked;
    if(this.checked) {
        endDateInput.value = '';
    }
});

// Load experience data
async function loadExperience() {
    try {
        const response = await fetch(`get_experience.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        const experienceContainer = document.querySelector('.experience-items');
        experienceContainer.innerHTML = data.map(exp => `
            <div class="experience-item">
                <img src="${exp.company_logo || 'default_company.png'}" alt="${exp.company_name}" class="company-logo">
                <div class="experience-details">
                    <h3>${exp.position}</h3>
                    <div class="company-info">${exp.company_name}</div>
                    <div class="date-range">${formatDateRange(exp.start_date, exp.end_date, exp.currently_working)}</div>
                    <div class="location">${exp.location}</div>
                    ${exp.description ? `<p class="description">${exp.description}</p>` : ''}
                </div>
                ${isOwnProfile ? `
                    <div class="item-actions">
                        <button onclick="editExperience(${exp.id})" class="edit-btn">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button onclick="deleteExperience(${exp.id})" class="delete-btn">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                ` : ''}
            </div>
        `).join('');
    } catch(err) {
        console.error('Error loading experience:', err);
    }
} 