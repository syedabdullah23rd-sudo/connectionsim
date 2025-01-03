<div id="addExperienceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add experience</h2>
            <button class="close-modal">&times;</button>
        </div>
        <form id="experienceForm">
            <div class="form-group">
                <label>Title*</label>
                <input type="text" name="position" required>
            </div>
            
            <div class="form-group">
                <label>Company name*</label>
                <input type="text" name="company_name" required>
            </div>
            
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Start date*</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End date</label>
                    <input type="date" name="end_date" id="endDate">
                </div>
            </div>
            
            <div class="form-check">
                <input type="checkbox" id="currentlyWorking" name="currently_working">
                <label for="currentlyWorking">I am currently working in this role</label>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('addExperienceModal')">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div> 