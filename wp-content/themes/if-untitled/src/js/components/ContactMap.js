/**
 * Google map on Contact: loads the Maps JavaScript API on demand (only where [data-contact-map] exists), shows one
 * custom marker per location and fits them in view. The dark style lives in Google Cloud (Map ID), not in code.
 * Data: data-key, data-map-id, data-markers (JSON [{lat, lng, title}]).
 */
export default class ContactMap {
    callbackName = '__ifUntitledMapsReady';
    markerClass = 'contact-map__marker';

    constructor({ selector = '[data-contact-map]', maxZoom = 15, padding = 80 } = {}) {
        this.element = document.querySelector(selector);
        if (this.element === null) return;
        const { key, mapId, markers } = this.element.dataset;
        this.key = key;
        this.mapId = mapId;
        this.markers = JSON.parse(markers || '[]');
        if (!this.key || this.markers.length === 0) return;
        this.maxZoom = maxZoom;
        this.padding = padding;
        this.main();
    }

    async main() {
        try {
            await this.#loadApi();
            const { importLibrary } = window.google.maps;
            const [{ Map }, { AdvancedMarkerElement }, { LatLngBounds }] = await Promise.all([
                importLibrary('maps'),
                importLibrary('marker'),
                importLibrary('core'),
            ]);

            const map = new Map(this.element, {
                mapId: this.mapId,
                center: this.markers[0],
                zoom: this.maxZoom,
                disableDefaultUI: true,
                zoomControl: true,
                gestureHandling: 'cooperative',
            });
            const bounds = new LatLngBounds();
            for (const { lat, lng, title } of this.markers) {
                const content = document.createElement('div');
                content.className = this.markerClass;
                new AdvancedMarkerElement({ map, position: { lat, lng }, title, content });
                bounds.extend({ lat, lng });
            }
            if (this.markers.length > 1) {
                map.fitBounds(bounds, this.padding);
            }
        } catch (error) {
            console.warn('ContactMap:', error);
        }
    }

    #loadApi() {
        if (window.google?.maps?.importLibrary) return Promise.resolve();
        return new Promise((resolve, reject) => {
            window[this.callbackName] = resolve;
            const params = new URLSearchParams({
                key: this.key,
                v: 'weekly',
                loading: 'async',
                callback: this.callbackName,
            });
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?${params}`;
            script.async = true;
            script.onerror = () => reject(new Error('Google Maps failed to load'));
            document.head.append(script);
        });
    }
}
