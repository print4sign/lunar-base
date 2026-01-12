/**
 * Standalone Designer - Pure Alpine.js + Fabric.js without Livewire
 * This avoids DOM conflicts between Livewire's morphing and Fabric.js canvas manipulation
 */
import Alpine from 'alpinejs';
import * as fabric from 'fabric';

// Make Alpine available globally
window.Alpine = Alpine;

// Register the Alpine component
function registerStandaloneDesigner() {
    Alpine.data('standaloneDesigner', ({
        config,
        designs,
        isFrontBack,
        cartLineId,
        uploaderIndex,
        saveUrl,
        confirmUrl,
        csrfToken,
    }) => ({
        // Canvas and config
        canvas: null,
        config: config,

        // Side management (for frontback type)
        currentSide: 'front',
        isFrontBack: isFrontBack,
        designs: designs || { front: null, back: null },

        // Tools
        currentTool: 'select',

        // Layers
        layers: [],
        selectedLayerId: null,

        // Selection state
        hasSelection: false,
        selectedProps: {
            fill: '#000000',
            stroke: '#000000',
            opacity: 1,
            fontSize: 24,
            fontWeight: 'normal',
            type: null,
        },

        // History for undo/redo
        history: [],
        historyIndex: -1,
        maxHistory: 30,
        canUndo: false,
        canRedo: false,

        // Save state
        isSaving: false,
        lastSaved: false,
        saveTimeout: null,
        isConfirming: false,

        // Zone overlay references
        zoneObjects: null,

        // URLs for API calls
        saveUrl: saveUrl,
        confirmUrl: confirmUrl,
        csrfToken: csrfToken,

        get canConfirm() {
            if (this.isFrontBack) {
                return this.hasDesign('front') && this.hasDesign('back');
            }
            return this.hasDesign('front');
        },

        init() {
            this.$nextTick(() => {
                this.initCanvas();
                this.drawZones();

                // Load existing design for current side
                const currentDesign = this.designs[this.currentSide];
                if (currentDesign) {
                    this.loadDesignFromJSON(currentDesign);
                }

                this.setupEventListeners();
                this.saveHistory();
            });
        },

        initCanvas() {
            // Wait for fabric to be available
            if (typeof fabric === 'undefined') {
                console.error('Fabric.js not loaded');
                return;
            }

            this.canvas = new fabric.Canvas(this.$refs.canvas, {
                width: this.config.width,
                height: this.config.height,
                backgroundColor: '#ffffff',
                selection: true,
                preserveObjectStacking: true,
            });

            this.scaleCanvasToFit();

            // Re-scale on window resize
            window.addEventListener('resize', () => this.scaleCanvasToFit());
        },

        scaleCanvasToFit() {
            const container = this.$refs.canvas.parentElement;
            const maxWidth = container.clientWidth - 64;
            const maxHeight = container.clientHeight - 64;

            const scaleX = maxWidth / this.config.width;
            const scaleY = maxHeight / this.config.height;
            const scale = Math.min(scaleX, scaleY, 1);

            this.canvas.setZoom(scale);
            this.canvas.setDimensions({
                width: this.config.width * scale,
                height: this.config.height * scale,
            });
        },

        drawZones() {
            const { trimX, trimY, trimWidth, trimHeight, safeMargin, width, height, bleedTop, bleedRight, bleedBottom, bleedLeft } = this.config;

            // Check if there's actual bleed (not all zeros)
            const hasBleed = bleedTop > 0 || bleedRight > 0 || bleedBottom > 0 || bleedLeft > 0;

            // Bleed zone (outer red dashed) - only draw if there's actual bleed
            let bleedRect = null;
            if (hasBleed) {
                bleedRect = new fabric.Rect({
                    left: 0,
                    top: 0,
                    width: width,
                    height: height,
                    fill: 'transparent',
                    stroke: '#ef4444',
                    strokeWidth: 2,
                    strokeDashArray: [8, 4],
                    selectable: false,
                    evented: false,
                    excludeFromExport: true,
                    name: '__zone_bleed',
                });
            }

            // Trim line (cyan solid) - the cut line
            const trimRect = new fabric.Rect({
                left: trimX,
                top: trimY,
                width: trimWidth,
                height: trimHeight,
                fill: 'transparent',
                stroke: '#06b6d4',
                strokeWidth: 2,
                selectable: false,
                evented: false,
                excludeFromExport: true,
                name: '__zone_trim',
            });

            // Safe zone (green dashed) - content should stay inside
            const safeRect = new fabric.Rect({
                left: trimX + safeMargin,
                top: trimY + safeMargin,
                width: trimWidth - (safeMargin * 2),
                height: trimHeight - (safeMargin * 2),
                fill: 'transparent',
                stroke: '#22c55e',
                strokeWidth: 1,
                strokeDashArray: [4, 4],
                selectable: false,
                evented: false,
                excludeFromExport: true,
                name: '__zone_safe',
            });

            // Add zones in order (bleed first if exists, then trim, then safe)
            if (bleedRect) {
                this.canvas.add(bleedRect);
            }
            this.canvas.add(trimRect, safeRect);

            this.zoneObjects = { bleedRect, trimRect, safeRect };
        },

        setupEventListeners() {
            this.canvas.on('selection:created', (e) => this.onSelectionChange(e));
            this.canvas.on('selection:updated', (e) => this.onSelectionChange(e));
            this.canvas.on('selection:cleared', () => this.onSelectionCleared());

            this.canvas.on('object:modified', () => {
                this.saveHistory();
                this.updateLayers();
                this.scheduleAutoSave();
            });

            this.canvas.on('object:added', (e) => {
                if (!e.target.name?.startsWith('__zone_')) {
                    this.updateLayers();
                }
            });

            this.canvas.on('object:removed', (e) => {
                if (!e.target.name?.startsWith('__zone_')) {
                    this.updateLayers();
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea, [contenteditable]')) return;

                if (e.key === 'Delete' || e.key === 'Backspace') {
                    if (this.hasSelection) {
                        e.preventDefault();
                        this.deleteSelected();
                    }
                }
                if (e.key === 'z' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    e.shiftKey ? this.redo() : this.undo();
                }
            });
        },

        onSelectionChange(e) {
            const selected = e.selected?.[0];
            if (selected && !selected.name?.startsWith('__zone_')) {
                this.hasSelection = true;
                this.selectedLayerId = selected.id;
                this.selectedProps = {
                    type: selected.type,
                    fill: selected.fill || '#000000',
                    stroke: selected.stroke || '#000000',
                    opacity: selected.opacity ?? 1,
                    fontSize: selected.fontSize || 24,
                    fontWeight: selected.fontWeight || 'normal',
                };
            }
        },

        onSelectionCleared() {
            this.hasSelection = false;
            this.selectedLayerId = null;
        },

        // Side switching (for frontback type)
        switchSide(side) {
            if (side === this.currentSide) return;

            // Save current design before switching
            this.saveDesignToServer();

            // Store current design in memory
            this.designs[this.currentSide] = this.getExportableJSON();

            // Switch side
            this.currentSide = side;

            // Clear canvas and redraw zones
            this.canvas.clear();
            this.canvas.backgroundColor = '#ffffff';
            this.drawZones();

            // Load design for new side
            const newDesign = this.designs[side];
            if (newDesign) {
                this.loadDesignFromJSON(newDesign);
            }

            // Reset history for new side
            this.history = [];
            this.historyIndex = -1;
            this.saveHistory();
        },

        hasDesign(side) {
            const design = this.designs[side];
            if (!design) return false;

            try {
                const parsed = typeof design === 'string' ? JSON.parse(design) : design;
                // Check if there are any user objects (not zone objects)
                return parsed.objects?.some(obj => !obj.name?.startsWith('__zone_'));
            } catch {
                return false;
            }
        },

        setTool(tool) {
            this.currentTool = tool;
            this.canvas.isDrawingMode = false;
            this.canvas.selection = tool === 'select';
        },

        addText() {
            const text = new fabric.Textbox('Tekst hier', {
                left: this.config.trimX + 20,
                top: this.config.trimY + 20,
                fontSize: 32,
                fontFamily: 'Arial, sans-serif',
                fill: '#000000',
                id: this.generateId(),
                name: 'Tekst',
            });
            this.canvas.add(text);
            this.canvas.setActiveObject(text);
            this.bringZonesToFront();
            this.saveHistory();
            this.scheduleAutoSave();
        },

        addShape(type) {
            const commonProps = {
                left: this.config.trimX + 30,
                top: this.config.trimY + 30,
                fill: '#3b82f6',
                stroke: '#1e40af',
                strokeWidth: 2,
                id: this.generateId(),
            };

            let shape;
            switch (type) {
                case 'rect':
                    shape = new fabric.Rect({ ...commonProps, width: 100, height: 80, name: 'Rechthoek' });
                    break;
                case 'circle':
                    shape = new fabric.Circle({ ...commonProps, radius: 50, name: 'Cirkel' });
                    break;
                case 'triangle':
                    shape = new fabric.Triangle({ ...commonProps, width: 100, height: 100, name: 'Driehoek' });
                    break;
                case 'line':
                    shape = new fabric.Line([0, 0, 150, 0], { ...commonProps, fill: null, name: 'Lijn' });
                    break;
            }

            if (shape) {
                this.canvas.add(shape);
                this.canvas.setActiveObject(shape);
                this.bringZonesToFront();
                this.saveHistory();
                this.scheduleAutoSave();
            }
        },

        uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                fabric.FabricImage.fromURL(e.target.result).then((img) => {
                    const maxW = this.config.trimWidth * 0.8;
                    const maxH = this.config.trimHeight * 0.8;
                    const scale = Math.min(maxW / img.width, maxH / img.height, 1);

                    img.set({
                        left: this.config.trimX + 20,
                        top: this.config.trimY + 20,
                        scaleX: scale,
                        scaleY: scale,
                        id: this.generateId(),
                        name: file.name.substring(0, 20),
                    });

                    this.canvas.add(img);
                    this.canvas.setActiveObject(img);
                    this.bringZonesToFront();
                    this.saveHistory();
                    this.scheduleAutoSave();
                });
            };
            reader.readAsDataURL(file);
            event.target.value = '';
        },

        updateLayers() {
            const objects = this.canvas.getObjects().filter(obj => !obj.name?.startsWith('__zone_'));
            this.layers = objects.map((obj, i) => ({
                id: obj.id,
                name: obj.name || `Object ${i + 1}`,
                type: obj.type,
            })).reverse();
        },

        selectLayer(id) {
            const obj = this.canvas.getObjects().find(o => o.id === id);
            if (obj) {
                this.canvas.setActiveObject(obj);
                this.canvas.renderAll();
            }
        },

        moveLayerUp(id) {
            const obj = this.canvas.getObjects().find(o => o.id === id);
            if (obj) {
                this.canvas.bringObjectForward(obj);
                this.bringZonesToFront();
                this.updateLayers();
                this.saveHistory();
                this.scheduleAutoSave();
            }
        },

        moveLayerDown(id) {
            const obj = this.canvas.getObjects().find(o => o.id === id);
            if (obj) {
                this.canvas.sendObjectBackwards(obj);
                this.updateLayers();
                this.saveHistory();
                this.scheduleAutoSave();
            }
        },

        updateSelectedProperty(prop, value) {
            const obj = this.canvas.getActiveObject();
            if (obj) {
                obj.set(prop, value);
                this.canvas.renderAll();
                this.saveHistory();
                this.scheduleAutoSave();
            }
        },

        // History management
        saveHistory() {
            const json = this.canvas.toJSON(['id', 'name', 'excludeFromExport']);

            if (this.historyIndex < this.history.length - 1) {
                this.history = this.history.slice(0, this.historyIndex + 1);
            }

            this.history.push(json);

            if (this.history.length > this.maxHistory) {
                this.history.shift();
            } else {
                this.historyIndex++;
            }

            this.updateHistoryButtons();
        },

        updateHistoryButtons() {
            this.canUndo = this.historyIndex > 0;
            this.canRedo = this.historyIndex < this.history.length - 1;
        },

        undo() {
            if (!this.canUndo) return;
            this.historyIndex--;
            this.loadFromHistory();
        },

        redo() {
            if (!this.canRedo) return;
            this.historyIndex++;
            this.loadFromHistory();
        },

        loadFromHistory() {
            const state = this.history[this.historyIndex];
            this.canvas.loadFromJSON(state).then(() => {
                this.canvas.renderAll();
                this.updateLayers();
                this.updateHistoryButtons();
                this.scheduleAutoSave();
            });
        },

        deleteSelected() {
            const objects = this.canvas.getActiveObjects();
            objects.forEach(obj => {
                if (!obj.name?.startsWith('__zone_')) {
                    this.canvas.remove(obj);
                }
            });
            this.canvas.discardActiveObject();
            this.saveHistory();
            this.scheduleAutoSave();
        },

        clearCanvas() {
            if (!confirm('Weet je zeker dat je alles wilt wissen?')) return;
            const objects = this.canvas.getObjects().filter(obj => !obj.name?.startsWith('__zone_'));
            objects.forEach(obj => this.canvas.remove(obj));
            this.saveHistory();
            this.scheduleAutoSave();
        },

        // Export/Import
        getExportableJSON() {
            const allObjects = this.canvas.toJSON(['id', 'name']);
            allObjects.objects = allObjects.objects.filter(obj => !obj.name?.startsWith('__zone_'));
            return JSON.stringify(allObjects);
        },

        loadDesignFromJSON(designJson) {
            if (!designJson) return;

            try {
                const json = typeof designJson === 'string' ? JSON.parse(designJson) : designJson;
                this.canvas.loadFromJSON(json).then(() => {
                    this.drawZones(); // Re-add zones after loading
                    this.canvas.renderAll();
                    this.updateLayers();
                });
            } catch (e) {
                console.error('Failed to load design:', e);
            }
        },

        // Auto-save with debounce
        scheduleAutoSave() {
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
            }
            this.saveTimeout = setTimeout(() => {
                this.saveDesignToServer();
            }, 1000); // Save 1 second after last change
        },

        async saveDesignToServer() {
            if (this.isSaving) return;

            this.isSaving = true;
            this.lastSaved = false;

            // Update current design in memory
            this.designs[this.currentSide] = this.getExportableJSON();

            try {
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        side: this.currentSide,
                        design: this.designs[this.currentSide],
                    }),
                });

                if (response.ok) {
                    this.lastSaved = true;
                    setTimeout(() => {
                        this.lastSaved = false;
                    }, 3000);
                } else {
                    console.error('Failed to save design');
                }
            } catch (e) {
                console.error('Error saving design:', e);
            } finally {
                this.isSaving = false;
            }
        },

        async confirmAndClose() {
            if (!this.canConfirm || this.isConfirming) return;

            this.isConfirming = true;

            // Make sure current design is saved
            this.designs[this.currentSide] = this.getExportableJSON();

            try {
                // Save current side first
                await this.saveDesignToServer();

                // Then confirm
                const response = await fetch(this.confirmUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({}),
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    console.error('Failed to confirm design');
                    alert('Er is iets misgegaan. Probeer het opnieuw.');
                }
            } catch (e) {
                console.error('Error confirming design:', e);
                alert('Er is iets misgegaan. Probeer het opnieuw.');
            } finally {
                this.isConfirming = false;
            }
        },

        generateId() {
            return 'obj_' + Math.random().toString(36).substring(2, 11);
        },

        bringZonesToFront() {
            if (this.zoneObjects) {
                Object.values(this.zoneObjects).forEach(obj => {
                    if (obj) {
                        this.canvas.bringObjectToFront(obj);
                    }
                });
            }
        },
    }));
}

// Register the component and start Alpine
registerStandaloneDesigner();
Alpine.start();
