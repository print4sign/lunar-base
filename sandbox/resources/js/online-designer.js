import * as fabric from 'fabric';

export default function onlineDesigner({ config, design, side }) {
    return {
        canvas: null,
        config: config,
        currentSide: side,
        currentTool: 'select',

        // State
        layers: [],
        selectedLayerId: null,
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

        // Zone overlay references
        zoneObjects: null,

        init() {
            this.$nextTick(() => {
                this.initCanvas();
                this.drawZones();
                if (design) {
                    this.loadDesign(design);
                }
                this.setupEventListeners();
                this.saveHistory();
            });
        },

        initCanvas() {
            this.canvas = new fabric.Canvas(this.$refs.canvas, {
                width: this.config.width,
                height: this.config.height,
                backgroundColor: '#ffffff',
                selection: true,
                preserveObjectStacking: true,
            });

            this.scaleCanvasToFit();
        },

        scaleCanvasToFit() {
            const container = this.$refs.canvas.parentElement;
            const maxWidth = container.clientWidth - 48;
            const maxHeight = container.clientHeight - 48;

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
            const { trimX, trimY, trimWidth, trimHeight, safeMargin, width, height } = this.config;

            // Bleed zone (outer red dashed)
            const bleedRect = new fabric.Rect({
                left: 0,
                top: 0,
                width: width - 1,
                height: height - 1,
                fill: 'transparent',
                stroke: '#ef4444',
                strokeWidth: 2,
                strokeDashArray: [8, 4],
                selectable: false,
                evented: false,
                excludeFromExport: true,
                name: '__zone_bleed',
            });

            // Trim line (cyan solid)
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

            // Safe zone (green dashed)
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

            this.canvas.add(bleedRect, trimRect, safeRect);
            this.zoneObjects = { bleedRect, trimRect, safeRect };
        },

        setupEventListeners() {
            this.canvas.on('selection:created', (e) => this.onSelectionChange(e));
            this.canvas.on('selection:updated', (e) => this.onSelectionChange(e));
            this.canvas.on('selection:cleared', () => this.onSelectionCleared());

            this.canvas.on('object:modified', () => {
                this.saveHistory();
                this.updateLayers();
                this.emitDesignUpdate();
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
            this.emitDesignUpdate();
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
                this.emitDesignUpdate();
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
                    this.emitDesignUpdate();
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
                this.emitDesignUpdate();
            }
        },

        moveLayerDown(id) {
            const obj = this.canvas.getObjects().find(o => o.id === id);
            if (obj) {
                this.canvas.sendObjectBackwards(obj);
                this.updateLayers();
                this.saveHistory();
                this.emitDesignUpdate();
            }
        },

        updateSelectedProperty(prop, value) {
            const obj = this.canvas.getActiveObject();
            if (obj) {
                obj.set(prop, value);
                this.canvas.renderAll();
                this.saveHistory();
                this.emitDesignUpdate();
            }
        },

        // History
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
                this.emitDesignUpdate();
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
            this.emitDesignUpdate();
        },

        clearCanvas() {
            if (!confirm('Weet je zeker dat je alles wilt wissen?')) return;
            const objects = this.canvas.getObjects().filter(obj => !obj.name?.startsWith('__zone_'));
            objects.forEach(obj => this.canvas.remove(obj));
            this.saveHistory();
            this.emitDesignUpdate();
        },

        saveDesign() {
            const json = JSON.stringify(this.getExportableJSON());
            this.$wire.saveDesign(json, this.currentSide);
        },

        getExportableJSON() {
            const allObjects = this.canvas.toJSON(['id', 'name']);
            allObjects.objects = allObjects.objects.filter(obj => !obj.name?.startsWith('__zone_'));
            return allObjects;
        },

        loadDesign(designJson) {
            if (!designJson) return;

            try {
                const json = typeof designJson === 'string' ? JSON.parse(designJson) : designJson;
                this.canvas.loadFromJSON(json).then(() => {
                    this.drawZones();
                    this.canvas.renderAll();
                    this.updateLayers();
                });
            } catch (e) {
                console.error('Failed to load design:', e);
            }
        },

        emitDesignUpdate() {
            const json = JSON.stringify(this.getExportableJSON());
            this.$wire.call('saveDesign', json, this.currentSide);
        },

        generateId() {
            return 'obj_' + Math.random().toString(36).substring(2, 11);
        },

        bringZonesToFront() {
            if (this.zoneObjects) {
                Object.values(this.zoneObjects).forEach(obj => {
                    this.canvas.bringObjectToFront(obj);
                });
            }
        },
    };
}
