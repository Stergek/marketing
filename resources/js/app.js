import './bootstrap';


document.addEventListener('livewire:initialized', () => {
    Livewire.on('download-file', (event) => {
        const url = `/storage/${event.path}`;
        const link = document.createElement('a');
        link.href = url;
        link.download = event.name;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});