/**
 * Google map on Contact: loads the Maps JavaScript API on demand (only where [data-contact-map] exists), shows one
 * custom marker per location and fits them in view.
 * Default (no Map ID): dark black-and-white style in code — no country, city or place labels, only road names.
 * With a Map ID the style comes from Google Cloud (cloud styling ignores `styles`), markers are advanced markers.
 * Data: data-key, data-map-id (optional), data-markers (JSON [{lat, lng, title}]).
 */

// Dark greys, minimal lettering: country / city / place / POI / transit labels off, road names dimmed.
const MAP_STYLES = [
    { elementType: 'geometry', stylers: [{ color: '#1a1a1a' }] },
    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#6b6b6b' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#1a1a1a' }] },
    { featureType: 'administrative', elementType: 'labels', stylers: [{ visibility: 'off' }] },
    { featureType: 'administrative', elementType: 'geometry', stylers: [{ color: '#333333' }] },
    { featureType: 'administrative.land_parcel', stylers: [{ visibility: 'off' }] },
    { featureType: 'poi', stylers: [{ visibility: 'off' }] },
    { featureType: 'transit', stylers: [{ visibility: 'off' }] },
    { featureType: 'landscape', elementType: 'geometry', stylers: [{ color: '#141414' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#2b2b2b' }] },
    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#3a3a3a' }] },
    { featureType: 'road.highway', elementType: 'labels', stylers: [{ visibility: 'off' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#000000' }] },
    { featureType: 'water', elementType: 'labels', stylers: [{ visibility: 'off' }] },
];

export default class ContactMap {
    callbackName = '__ifUntitledMapsReady';
    markerClass = 'contact-map__marker';

    constructor({ selector = '[data-contact-map]', maxZoom = 15, padding = 80 } = {}) {
        this.element = document.querySelector(selector);
        if (this.element === null) return;
        const { key, mapId, markers } = this.element.dataset;
        this.key = key;
        this.mapId = mapId || '';
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
            const [{ Map, OverlayView }, { LatLngBounds, ColorScheme }] = await Promise.all([
                importLibrary('maps'),
                importLibrary('core'),
            ]);

            const map = new Map(this.element, {
                ...(this.mapId ? { mapId: this.mapId, colorScheme: ColorScheme.DARK } : { styles: MAP_STYLES }),
                backgroundColor: '#141414',
                center: this.markers[0],
                zoom: this.maxZoom,
                disableDefaultUI: true,
                zoomControl: true,
                gestureHandling: 'cooperative',
            });
            const addMarker = this.mapId ? await this.#advancedMarker(map) : this.#overlayMarker(map, OverlayView);
            const bounds = new LatLngBounds();
            for (const { lat, lng, title } of this.markers) {
                addMarker({ lat, lng }, title);
                bounds.extend({ lat, lng });
            }
            if (this.markers.length > 1) {
                map.fitBounds(bounds, this.padding);
            }
        } catch (error) {
            console.warn('ContactMap:', error);
        }
    }

    #markerElement(title) {
        const element = document.createElement('div');
        element.className = this.markerClass;
        element.title = title;
        return element;
    }

    // Advanced markers need a Map ID.
    async #advancedMarker(map) {
        const { AdvancedMarkerElement } = await window.google.maps.importLibrary('marker');
        return (position, title) =>
            new AdvancedMarkerElement({ map, position, title, content: this.#markerElement(title) });
    }

    // Without a Map ID: the same dot as a plain overlay, centred on its position.
    #overlayMarker(map, OverlayView) {
        const createElement = (title) => this.#markerElement(title);
        class DotMarker extends OverlayView {
            constructor(position, title) {
                super();
                this.position = position;
                this.element = createElement(title);
                this.element.style.position = 'absolute';
                this.element.style.transform = 'translate(-50%, -50%)';
            }

            onAdd() {
                this.getPanes().overlayMouseTarget.append(this.element);
            }

            draw() {
                const point = this.getProjection().fromLatLngToDivPixel(this.position);
                if (point) {
                    this.element.style.left = `${point.x}px`;
                    this.element.style.top = `${point.y}px`;
                }
            }

            onRemove() {
                this.element.remove();
            }
        }
        return (position, title) => {
            const marker = new DotMarker(new window.google.maps.LatLng(position), title);
            marker.setMap(map);
            return marker;
        };
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
