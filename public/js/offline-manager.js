// Offline Manager - Client-side utilities for PWA offline functionality
class OfflineManager {
    constructor() {
        this.dbName = 'EurekaOfflineDB';
        this.dbVersion = 2; // Increment version to fix schema
        this.db = null;
        this.isOnline = navigator.onLine;
        this.serviceWorker = null;
        
        this.init();
    }

    async init() {
        // Setup event listeners
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Register service worker
        if ('serviceWorker' in navigator) {
            try {
                this.serviceWorker = await navigator.serviceWorker.register('/sw-enhanced.js');
                console.log('Enhanced Service Worker registered');
                
                // Listen for service worker messages
                navigator.serviceWorker.addEventListener('message', event => {
                    this.handleServiceWorkerMessage(event);
                });
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
        
        // Initialize IndexedDB
        await this.openDB();
        
        // Setup UI indicators
        this.setupOfflineIndicator();
        
        // Cache essential user data if online
        if (this.isOnline) {
            this.cacheEssentialData();
        }
    }

    async openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores if they don't exist
                if (!db.objectStoreNames.contains('userData')) {
                    db.createObjectStore('userData', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('quizData')) {
                    db.createObjectStore('quizData', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('teamData')) {
                    db.createObjectStore('teamData', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('offlineSubmissions')) {
                    const store = db.createObjectStore('offlineSubmissions', { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    store.createIndex('timestamp', 'timestamp');
                    store.createIndex('type', 'type');
                }
            };
        });
    }

    handleOnline() {
        console.log('App is online');
        this.isOnline = true;
        this.updateOfflineIndicator(false);
        
        // Trigger sync of offline actions
        this.syncOfflineActions();
        
        // Notify service worker
        if (this.serviceWorker) {
            this.serviceWorker.postMessage({ type: 'SYNC_NOW' });
        }
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('app-online'));
    }

    handleOffline() {
        console.log('App is offline');
        this.isOnline = false;
        this.updateOfflineIndicator(true);
        
        // Dispatch custom event
        window.dispatchEvent(new CustomEvent('app-offline'));
    }

    setupOfflineIndicator() {
        // Create offline indicator if it doesn't exist
        if (!document.getElementById('offline-indicator')) {
            const indicator = document.createElement('div');
            indicator.id = 'offline-indicator';
            indicator.innerHTML = `
                <div class="offline-banner" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: #f59e0b;
                    color: white;
                    padding: 8px;
                    text-align: center;
                    z-index: 9999;
                    transform: translateY(-100%);
                    transition: transform 0.3s ease;
                    font-size: 14px;
                ">
                    📱 You're offline - Some features may be limited
                </div>
            `;
            document.body.appendChild(indicator);
        }
        
        // Set initial state
        this.updateOfflineIndicator(!this.isOnline);
    }

    updateOfflineIndicator(show) {
        const indicator = document.querySelector('#offline-indicator .offline-banner');
        if (indicator) {
            indicator.style.transform = show ? 'translateY(0)' : 'translateY(-100%)';
        }
    }

    // Cache essential user data
    async cacheEssentialData() {
        try {
            // Cache current user data
            const userData = await this.fetchUserData();
            if (userData) {
                await this.storeData('userData', 'current-user', userData);
            }
            
            // Cache user's team data
            const teamData = await this.fetchTeamData();
            if (teamData) {
                await this.storeData('teamData', 'current-team', teamData);
            }
            
            // Cache available quizzes
            const quizData = await this.fetchQuizData();
            if (quizData) {
                await this.storeData('quizData', 'available-quizzes', quizData);
            }
            
            console.log('Essential data cached for offline use');
        } catch (error) {
            console.error('Failed to cache essential data:', error);
        }
    }

    // Fetch user data from API
    async fetchUserData() {
        try {
            const response = await fetch('/api/user/current');
            return response.ok ? await response.json() : null;
        } catch (error) {
            console.warn('Failed to fetch user data:', error);
            return null;
        }
    }

    // Fetch team data from API
    async fetchTeamData() {
        try {
            const response = await fetch('/api/user/team');
            return response.ok ? await response.json() : null;
        } catch (error) {
            console.warn('Failed to fetch team data:', error);
            return null;
        }
    }

    // Fetch quiz data from API
    async fetchQuizData() {
        try {
            const response = await fetch('/api/quizzes/available');
            return response.ok ? await response.json() : null;
        } catch (error) {
            console.warn('Failed to fetch quiz data:', error);
            return null;
        }
    }

    // Store data in IndexedDB
    async storeData(storeName, key, data) {
        if (!this.db) return;
        
        const transaction = this.db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        
        await store.put({
            id: key,
            data: data,
            timestamp: Date.now()
        });
    }

    // Get data from IndexedDB
    async getData(storeName, key) {
        if (!this.db) return null;
        
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);
            
            request.onsuccess = () => {
                const result = request.result;
                resolve(result ? result.data : null);
            };
            request.onerror = () => reject(request.error);
        });
    }

    // Queue an action for offline execution
    async queueOfflineAction(action) {
        if (!this.db) return;
        
        const transaction = this.db.transaction(['offlineSubmissions'], 'readwrite');
        const store = transaction.objectStore('offlineSubmissions');
        
        const actionData = {
            ...action,
            timestamp: Date.now(),
            synced: false
        };
        
        await store.add(actionData);
        console.log('Queued offline action:', actionData);
    }

    // Sync offline actions when online
    async syncOfflineActions() {
        if (!this.db || !this.isOnline) return;
        
        const transaction = this.db.transaction(['offlineSubmissions'], 'readwrite');
        const store = transaction.objectStore('offlineSubmissions');
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = async () => {
                const actions = request.result.filter(action => !action.synced);
                console.log('Syncing offline actions:', actions.length);
                
                for (const action of actions) {
                    try {
                        await this.executeAction(action);
                        
                        // Mark as synced
                        action.synced = true;
                        store.put(action);
                    } catch (error) {
                        console.error('Failed to sync action:', action, error);
                    }
                }
                
                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    }

    // Execute a queued action
    async executeAction(action) {
        switch (action.type) {
            case 'quiz-submit':
                return await this.submitQuiz(action.data);
            case 'team-update':
                return await this.updateTeam(action.data);
            case 'checkin':
                return await this.submitCheckin(action.data);
            default:
                console.warn('Unknown action type:', action.type);
        }
    }

    // Submit quiz results
    async submitQuiz(quizData) {
        const response = await fetch('/api/quiz/submit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(quizData)
        });
        
        if (!response.ok) {
            throw new Error('Quiz submission failed');
        }
        
        return await response.json();
    }

    // Update team information
    async updateTeam(teamData) {
        const response = await fetch('/api/team/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(teamData)
        });
        
        if (!response.ok) {
            throw new Error('Team update failed');
        }
        
        return await response.json();
    }

    // Submit check-in
    async submitCheckin(checkinData) {
        const response = await fetch('/api/checkin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(checkinData)
        });
        
        if (!response.ok) {
            throw new Error('Check-in submission failed');
        }
        
        return await response.json();
    }

    // Handle service worker messages
    handleServiceWorkerMessage(event) {
        const { data } = event;
        
        switch (data.type) {
            case 'OFFLINE_ACTION_QUEUED':
                console.log('Action queued by service worker:', data);
                break;
            case 'SYNC_COMPLETE':
                console.log('Background sync completed');
                break;
        }
    }

    // Check if app can work offline for specific features
    canWorkOffline(feature) {
        const offlineCapabilities = {
            'quiz-view': true,
            'quiz-take': true,
            'team-view': true,
            'dashboard': true,
            'quest-locations': true,
            'admin-panel': false,
            'live-features': false
        };
        
        return offlineCapabilities[feature] || false;
    }

    // Get offline status
    getStatus() {
        return {
            isOnline: this.isOnline,
            hasServiceWorker: !!this.serviceWorker,
            hasIndexedDB: !!this.db,
            cacheSupported: 'caches' in window
        };
    }
}

// Initialize offline manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.offlineManager = new OfflineManager();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OfflineManager;
}