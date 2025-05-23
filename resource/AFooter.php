<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addBreedButton = document.getElementById('add-breed-button');
        const addBreedModal = document.getElementById('add-breed-modal');
        const closeBreedModal = document.getElementById('close-breed-modal');

        // Ensure modal is hidden on page load
        if (addBreedModal) { // Check if modal element exists on the page
            addBreedModal.style.display = 'none';
        }

        // This PHP block is specifically for AdminPet.php to re-open the modal after form submission
        // It should only be active if the current page is AdminPet.php and a relevant POST occurred.
        // We'll rely on the AdminPet.php to set a flag if needed.
        // For now, we'll remove the PHP logic from here and handle it directly in AdminPet.php if necessary.

        if (addBreedButton) { // Only add listener if button exists
            addBreedButton.addEventListener('click', () => {
                addBreedModal.style.display = 'flex';
            });
        }

        if (closeBreedModal) { // Only add listener if close button exists
            closeBreedModal.addEventListener('click', () => {
                addBreedModal.style.display = 'none';
            });
        }

        // Global click listener to close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target == addBreedModal) {
                addBreedModal.style.display = 'none';
            }
        });
    });
</script>

    <footer class="bg-gray-800 text-white py-6 text-center w-full mt-auto">
        <div class="container mx-auto">
            <p>&copy; 2025 Vet Clinic Admin. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>