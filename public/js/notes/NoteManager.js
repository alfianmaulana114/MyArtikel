/**
 * NoteManager - Component untuk mengelola notes dengan UI yang komprehensif
 */
class NoteManager {
    constructor(options = {}) {
        this.container = options.container || document.body;
        this.noteService = options.noteService || new NoteService();
        this.richTextEditor = null;
        this.currentNote = null;
        this.notes = [];
        this.filters = {
            search: "",
            category: "",
            type: "",
            tags: [],
            articleId: null,
        };
        this.isLoading = false;

        this.initializeComponent();
    }

    /**
     * Initialize component
     */
    initializeComponent() {
        this.createUI();
        this.attachEventListeners();
        this.loadNotes();
        this.initializeRichTextEditor();
    }

    /**
     * Create UI structure
     */
    createUI() {
        const uiHTML = `
            <div class="note-manager" id="note-manager">
                <div class="note-manager-header">
                    <h3>Notes Manager</h3>
                    <div class="note-manager-controls">
                        <button id="add-note-btn" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Note
                        </button>
                        <button id="sync-notes-btn" class="btn btn-secondary">
                            <i class="fas fa-sync"></i> Sync
                        </button>
                        <button id="search-notes-btn" class="btn btn-outline">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
                
                <div class="note-manager-filters" id="note-filters">
                    <div class="filter-group">
                        <input type="text" id="search-input" placeholder="Search notes..." class="form-control">
                    </div>
                    <div class="filter-group">
                        <select id="category-filter" class="form-control">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select id="type-filter" class="form-control">
                            <option value="">All Types</option>
                            <option value="personal">Personal</option>
                            <option value="research">Research</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select id="tags-filter" class="form-control" multiple>
                            <option value="">All Tags</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>
                            <input type="checkbox" id="anchored-filter"> Anchored Only
                        </label>
                    </div>
                </div>
                
                <div class="note-manager-content">
                    <div class="note-list-panel">
                        <div class="note-list-header">
                            <span id="note-count">0 notes</span>
                            <div class="note-list-actions">
                                <select id="sort-by" class="form-control-sm">
                                    <option value="created_at">Date Created</option>
                                    <option value="updated_at">Date Updated</option>
                                    <option value="title">Title</option>
                                    <option value="category">Category</option>
                                </select>
                                <select id="sort-order" class="form-control-sm">
                                    <option value="desc">Newest First</option>
                                    <option value="asc">Oldest First</option>
                                </select>
                            </div>
                        </div>
                        <div class="note-list" id="note-list">
                            <div class="loading-indicator">
                                <i class="fas fa-spinner fa-spin"></i> Loading notes...
                            </div>
                        </div>
                    </div>
                    
                    <div class="note-editor-panel" id="note-editor-panel">
                        <div class="note-editor-header">
                            <input type="text" id="note-title" placeholder="Note title..." class="form-control">
                            <div class="note-editor-actions">
                                <button id="save-note-btn" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <button id="cancel-note-btn" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                        
                        <div class="note-editor-meta">
                            <div class="meta-group">
                                <label>Type:</label>
                                <select id="note-type" class="form-control-sm">
                                    <option value="personal">Personal</option>
                                    <option value="research">Research</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="meta-group">
                                <label>Category:</label>
                                <input type="text" id="note-category" placeholder="Category..." class="form-control-sm">
                            </div>
                            <div class="meta-group">
                                <label>Tags:</label>
                                <input type="text" id="note-tags" placeholder="Tags (comma-separated)..." class="form-control-sm">
                            </div>
                            <div class="meta-group">
                                <label>
                                    <input type="checkbox" id="note-private"> Private
                                </label>
                            </div>
                        </div>
                        
                        <div class="note-editor-anchoring" id="note-anchoring">
                            <div class="anchoring-group">
                                <label>Paragraph Index:</label>
                                <input type="number" id="note-paragraph-index" min="0" class="form-control-sm">
                            </div>
                            <div class="anchoring-group">
                                <label>Paragraph ID:</label>
                                <input type="text" id="note-paragraph-id" placeholder="Paragraph ID..." class="form-control-sm">
                            </div>
                            <div class="anchoring-group">
                                <label>Start Offset:</label>
                                <input type="number" id="note-start-offset" min="0" class="form-control-sm">
                            </div>
                            <div class="anchoring-group">
                                <label>End Offset:</label>
                                <input type="number" id="note-end-offset" min="0" class="form-control-sm">
                            </div>
                        </div>
                        
                        <div class="note-editor-content-type">
                            <label>
                                <input type="checkbox" id="note-rich-text"> Use Rich Text Editor
                            </label>
                        </div>
                        
                        <div class="note-editor-content">
                            <div id="note-plain-editor" class="plain-editor">
                                <textarea id="note-content" placeholder="Write your note here..." class="form-control"></textarea>
                            </div>
                            <div id="note-rich-editor" class="rich-editor" style="display: none;">
                                <div id="quill-editor"></div>
                            </div>
                        </div>
                        
                        <div class="note-editor-status" id="note-editor-status">
                            <span class="sync-status" id="sync-status">Synced</span>
                            <span class="word-count" id="word-count">0 words</span>
                            <span class="char-count" id="char-count">0 characters</span>
                        </div>
                    </div>
                </div>
                
                <div class="note-manager-footer">
                    <div class="sync-info">
                        <span id="sync-info-text">Last synced: Never</span>
                        <span id="offline-indicator" class="offline-indicator" style="display: none;">
                            <i class="fas fa-wifi-slash"></i> Offline
                        </span>
                    </div>
                    <div class="sync-controls">
                        <label>
                            <input type="checkbox" id="auto-sync" checked> Auto-sync
                        </label>
                    </div>
                </div>
            </div>
        `;

        this.container.insertAdjacentHTML("beforeend", uiHTML);
        this.bindElements();
    }

    /**
     * Bind UI elements
     */
    bindElements() {
        // Main elements
        this.elements = {
            addNoteBtn: document.getElementById("add-note-btn"),
            syncNotesBtn: document.getElementById("sync-notes-btn"),
            searchNotesBtn: document.getElementById("search-notes-btn"),
            searchInput: document.getElementById("search-input"),
            categoryFilter: document.getElementById("category-filter"),
            typeFilter: document.getElementById("type-filter"),
            tagsFilter: document.getElementById("tags-filter"),
            anchoredFilter: document.getElementById("anchored-filter"),
            noteList: document.getElementById("note-list"),
            noteCount: document.getElementById("note-count"),
            sortBy: document.getElementById("sort-by"),
            sortOrder: document.getElementById("sort-order"),

            // Editor elements
            noteEditorPanel: document.getElementById("note-editor-panel"),
            noteTitle: document.getElementById("note-title"),
            noteType: document.getElementById("note-type"),
            noteCategory: document.getElementById("note-category"),
            noteTags: document.getElementById("note-tags"),
            notePrivate: document.getElementById("note-private"),
            noteParagraphIndex: document.getElementById("note-paragraph-index"),
            noteParagraphId: document.getElementById("note-paragraph-id"),
            noteStartOffset: document.getElementById("note-start-offset"),
            noteEndOffset: document.getElementById("note-end-offset"),
            noteRichText: document.getElementById("note-rich-text"),
            noteContent: document.getElementById("note-content"),
            plainEditor: document.getElementById("note-plain-editor"),
            richEditor: document.getElementById("note-rich-editor"),
            quillEditor: document.getElementById("quill-editor"),

            // Action buttons
            saveNoteBtn: document.getElementById("save-note-btn"),
            cancelNoteBtn: document.getElementById("cancel-note-btn"),

            // Status elements
            syncStatus: document.getElementById("sync-status"),
            wordCount: document.getElementById("word-count"),
            charCount: document.getElementById("char-count"),
            syncInfoText: document.getElementById("sync-info-text"),
            offlineIndicator: document.getElementById("offline-indicator"),
            autoSync: document.getElementById("auto-sync"),
        };
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        // Filter listeners
        this.elements.searchInput.addEventListener(
            "input",
            this.debounce(this.applyFilters.bind(this), 300),
        );
        this.elements.categoryFilter.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );
        this.elements.typeFilter.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );
        this.elements.tagsFilter.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );
        this.elements.anchoredFilter.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );
        this.elements.sortBy.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );
        this.elements.sortOrder.addEventListener(
            "change",
            this.applyFilters.bind(this),
        );

        // Action button listeners
        this.elements.addNoteBtn.addEventListener(
            "click",
            this.createNewNote.bind(this),
        );
        this.elements.syncNotesBtn.addEventListener(
            "click",
            this.syncNotes.bind(this),
        );
        this.elements.searchNotesBtn.addEventListener(
            "click",
            this.toggleSearchPanel.bind(this),
        );

        // Editor listeners
        this.elements.saveNoteBtn.addEventListener(
            "click",
            this.saveCurrentNote.bind(this),
        );
        this.elements.cancelNoteBtn.addEventListener(
            "click",
            this.cancelEditing.bind(this),
        );
        this.elements.noteRichText.addEventListener(
            "change",
            this.toggleEditorType.bind(this),
        );
        this.elements.noteContent.addEventListener(
            "input",
            this.updateContentStats.bind(this),
        );

        // Sync control listeners
        this.elements.autoSync.addEventListener(
            "change",
            this.toggleAutoSync.bind(this),
        );

        // Note service event listeners
        window.addEventListener(
            "notes-sync-conflicts",
            this.handleSyncConflicts.bind(this),
        );

        // Network status listeners
        window.addEventListener(
            "online",
            this.handleNetworkStatusChange.bind(this),
        );
        window.addEventListener(
            "offline",
            this.handleNetworkStatusChange.bind(this),
        );
    }

    /**
     * Initialize rich text editor
     */
    initializeRichTextEditor() {
        if (typeof Quill !== "undefined") {
            this.richTextEditor = new Quill(this.elements.quillEditor, {
                theme: "snow",
                modules: {
                    toolbar: [
                        ["bold", "italic", "underline", "strike"],
                        ["blockquote", "code-block"],
                        [{ header: 1 }, { header: 2 }],
                        [{ list: "ordered" }, { list: "bullet" }],
                        [{ script: "sub" }, { script: "super" }],
                        [{ indent: "-1" }, { indent: "+1" }],
                        [{ direction: "rtl" }],
                        [{ size: ["small", false, "large", "huge"] }],
                        [{ header: [1, 2, 3, 4, 5, 6, false] }],
                        [{ color: [] }, { background: [] }],
                        [{ font: [] }],
                        [{ align: [] }],
                        ["clean"],
                        ["link", "image"],
                    ],
                },
                placeholder: "Write your note here...",
            });

            this.richTextEditor.on(
                "text-change",
                this.updateContentStats.bind(this),
            );
        }
    }

    /**
     * Load notes
     */
    async loadNotes() {
        this.setLoading(true);

        try {
            const result = await this.noteService.fetchNotes(this.filters);
            this.notes = result.notes;
            this.renderNotesList();
            this.updateFilterOptions(result.filters);
            this.updateSyncInfo();
        } catch (error) {
            console.error("Error loading notes:", error);
            this.showError("Failed to load notes");
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Render notes list
     */
    renderNotesList() {
        const noteList = this.elements.noteList;

        if (this.notes.length === 0) {
            noteList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-sticky-note"></i>
                    <p>No notes found</p>
                    <button class="btn btn-primary" onclick="noteManager.createNewNote()">
                        Create your first note
                    </button>
                </div>
            `;
            this.elements.noteCount.textContent = "0 notes";
            return;
        }

        noteList.innerHTML = this.notes
            .map((note) => this.renderNoteItem(note))
            .join("");
        this.elements.noteCount.textContent = `${this.notes.length} note${this.notes.length !== 1 ? "s" : ""}`;

        // Add click listeners to note items
        noteList.querySelectorAll(".note-item").forEach((item) => {
            item.addEventListener("click", () =>
                this.selectNote(item.dataset.noteId),
            );
        });
    }

    /**
     * Render single note item
     */
    renderNoteItem(note) {
        const isAnchored =
            note.paragraph_index !== null || note.paragraph_id !== null;
        const isRichText = note.is_rich_text;
        const isPrivate = note.is_private;
        const isSynced = note.sync_status === "synced";

        return `
            <div class="note-item ${this.currentNote?.id === note.id ? "active" : ""}" 
                 data-note-id="${note.id}">
                <div class="note-item-header">
                    <h4 class="note-title">${note.title || "Untitled Note"}</h4>
                    <div class="note-item-meta">
                        ${isAnchored ? '<i class="fas fa-anchor" title="Anchored to paragraph"></i>' : ""}
                        ${isRichText ? '<i class="fas fa-font" title="Rich text"></i>' : ""}
                        ${isPrivate ? '<i class="fas fa-lock" title="Private"></i>' : ""}
                        ${!isSynced ? '<i class="fas fa-exclamation-triangle" title="Not synced"></i>' : ""}
                    </div>
                </div>
                <div class="note-item-content">
                    <p>${this.truncateText(note.content, 100)}</p>
                </div>
                <div class="note-item-footer">
                    <span class="note-category">${note.category || "No category"}</span>
                    <span class="note-date">${this.formatDate(note.updated_at)}</span>
                </div>
                ${
                    note.tags && note.tags.length > 0
                        ? `
                    <div class="note-item-tags">
                        ${note.tags.map((tag) => `<span class="tag">${tag}</span>`).join("")}
                    </div>
                `
                        : ""
                }
            </div>
        `;
    }

    /**
     * Create new note
     */
    createNewNote() {
        this.currentNote = {
            id: null,
            title: "",
            content: "",
            type: "personal",
            category: "",
            tags: [],
            is_private: false,
            is_rich_text: false,
            content_json: null,
            paragraph_index: null,
            paragraph_id: null,
            start_offset: null,
            end_offset: null,
        };

        this.showEditor();
        this.populateEditor();
    }

    /**
     * Select note for editing
     */
    async selectNote(noteId) {
        try {
            const note = await this.noteService.fetchNote(noteId);
            this.currentNote = note;
            this.showEditor();
            this.populateEditor();
            this.highlightNoteInList(noteId);
        } catch (error) {
            console.error("Error selecting note:", error);
            this.showError("Failed to load note");
        }
    }

    /**
     * Show editor panel
     */
    showEditor() {
        this.elements.noteEditorPanel.style.display = "block";
        this.updateContentStats();
    }

    /**
     * Hide editor panel
     */
    hideEditor() {
        this.elements.noteEditorPanel.style.display = "none";
        this.currentNote = null;
    }

    /**
     * Populate editor with current note data
     */
    populateEditor() {
        if (!this.currentNote) return;

        this.elements.noteTitle.value = this.currentNote.title || "";
        this.elements.noteType.value = this.currentNote.type || "personal";
        this.elements.noteCategory.value = this.currentNote.category || "";
        this.elements.noteTags.value = this.currentNote.tags
            ? this.currentNote.tags.join(", ")
            : "";
        this.elements.notePrivate.checked =
            this.currentNote.is_private || false;
        this.elements.noteRichText.checked =
            this.currentNote.is_rich_text || false;

        // Anchoring data
        this.elements.noteParagraphIndex.value =
            this.currentNote.paragraph_index || "";
        this.elements.noteParagraphId.value =
            this.currentNote.paragraph_id || "";
        this.elements.noteStartOffset.value =
            this.currentNote.start_offset || "";
        this.elements.noteEndOffset.value = this.currentNote.end_offset || "";

        // Content
        if (this.currentNote.is_rich_text && this.currentNote.content_json) {
            this.toggleEditorType();
            if (this.richTextEditor) {
                this.richTextEditor.setContents(this.currentNote.content_json);
            }
        } else {
            this.elements.noteContent.value = this.currentNote.content || "";
        }

        this.updateContentStats();
    }

    /**
     * Save current note
     */
    async saveCurrentNote() {
        if (!this.currentNote) return;

        const noteData = this.collectEditorData();

        try {
            this.setLoading(true);

            let savedNote;
            if (this.currentNote.id) {
                savedNote = await this.noteService.updateNote(
                    this.currentNote.id,
                    noteData,
                );
            } else {
                savedNote = await this.noteService.createNote(noteData);
            }

            this.currentNote = savedNote;
            this.showSuccess("Note saved successfully");
            this.loadNotes();
        } catch (error) {
            console.error("Error saving note:", error);
            this.showError("Failed to save note");
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Collect editor data
     */
    collectEditorData() {
        const noteData = {
            title: this.elements.noteTitle.value,
            type: this.elements.noteType.value,
            category: this.elements.noteCategory.value,
            tags: this.elements.noteTags.value
                .split(",")
                .map((tag) => tag.trim())
                .filter((tag) => tag),
            is_private: this.elements.notePrivate.checked,
            is_rich_text: this.elements.noteRichText.checked,
            article_id: this.currentNote.article_id, // Keep existing article ID

            // Anchoring data
            paragraph_index: this.elements.noteParagraphIndex.value
                ? parseInt(this.elements.noteParagraphIndex.value)
                : null,
            paragraph_id: this.elements.noteParagraphId.value || null,
            start_offset: this.elements.noteStartOffset.value
                ? parseInt(this.elements.noteStartOffset.value)
                : null,
            end_offset: this.elements.noteEndOffset.value
                ? parseInt(this.elements.noteEndOffset.value)
                : null,
        };

        // Content
        if (noteData.is_rich_text && this.richTextEditor) {
            noteData.content_json = this.richTextEditor.getContents();
            noteData.content = this.extractTextFromRichContent(
                noteData.content_json,
            );
        } else {
            noteData.content = this.elements.noteContent.value;
        }

        return noteData;
    }

    /**
     * Cancel editing
     */
    cancelEditing() {
        this.hideEditor();
        this.clearEditor();
    }

    /**
     * Clear editor
     */
    clearEditor() {
        this.elements.noteTitle.value = "";
        this.elements.noteContent.value = "";
        this.elements.noteCategory.value = "";
        this.elements.noteTags.value = "";
        this.elements.notePrivate.checked = false;
        this.elements.noteRichText.checked = false;

        if (this.richTextEditor) {
            this.richTextEditor.setContents([]);
        }

        this.updateContentStats();
    }

    /**
     * Toggle editor type (plain vs rich text)
     */
    toggleEditorType() {
        const isRichText = this.elements.noteRichText.checked;

        if (isRichText) {
            this.elements.plainEditor.style.display = "none";
            this.elements.richEditor.style.display = "block";

            // Sync content from plain to rich editor
            if (this.richTextEditor && this.elements.noteContent.value) {
                this.richTextEditor.setText(this.elements.noteContent.value);
            }
        } else {
            this.elements.plainEditor.style.display = "block";
            this.elements.richEditor.style.display = "none";

            // Sync content from rich to plain editor
            if (this.richTextEditor) {
                this.elements.noteContent.value = this.richTextEditor.getText();
            }
        }

        this.updateContentStats();
    }

    /**
     * Update content statistics
     */
    updateContentStats() {
        let content = "";

        if (this.elements.noteRichText.checked && this.richTextEditor) {
            content = this.richTextEditor.getText();
        } else {
            content = this.elements.noteContent.value;
        }

        const words = content
            .trim()
            .split(/\s+/)
            .filter((word) => word.length > 0).length;
        const chars = content.length;

        this.elements.wordCount.textContent = `${words} words`;
        this.elements.charCount.textContent = `${chars} characters`;
    }

    /**
     * Apply filters
     */
    async applyFilters() {
        this.filters = {
            search: this.elements.searchInput.value,
            category: this.elements.categoryFilter.value,
            type: this.elements.typeFilter.value,
            tags: Array.from(this.elements.tagsFilter.selectedOptions).map(
                (option) => option.value,
            ),
            anchored: this.elements.anchoredFilter.checked,
            sort_by: this.elements.sortBy.value,
            sort_order: this.elements.sortOrder.value,
        };

        await this.loadNotes();
    }

    /**
     * Sync notes
     */
    async syncNotes() {
        try {
            this.setLoading(true);
            const result = await this.noteService.syncNotes();
            this.showSuccess("Notes synced successfully");
            this.loadNotes();
            return result;
        } catch (error) {
            console.error("Error syncing notes:", error);
            this.showError("Failed to sync notes");
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Toggle search panel
     */
    toggleSearchPanel() {
        const filters = document.getElementById("note-filters");
        filters.style.display =
            filters.style.display === "none" ? "flex" : "none";
    }

    /**
     * Update filter options
     */
    updateFilterOptions(filters) {
        // Update categories
        if (filters.categories) {
            this.elements.categoryFilter.innerHTML =
                '<option value="">All Categories</option>';
            filters.categories.forEach((category) => {
                const option = document.createElement("option");
                option.value = category;
                option.textContent = category;
                this.elements.categoryFilter.appendChild(option);
            });
        }

        // Update tags
        if (filters.tags) {
            this.elements.tagsFilter.innerHTML =
                '<option value="">All Tags</option>';
            filters.tags.forEach((tag) => {
                const option = document.createElement("option");
                option.value = tag;
                option.textContent = tag;
                this.elements.tagsFilter.appendChild(option);
            });
        }
    }

    /**
     * Handle sync conflicts
     */
    handleSyncConflicts(event) {
        const conflicts = event.detail.conflicts;
        console.log("Sync conflicts:", conflicts);

        // Show conflict resolution UI
        this.showConflictResolutionDialog(conflicts);
    }

    /**
     * Show conflict resolution dialog
     */
    showConflictResolutionDialog(conflicts) {
        // Implement conflict resolution UI
        console.log(
            "Conflict resolution needed for:",
            conflicts.length,
            "notes",
        );
    }

    /**
     * Handle network status change
     */
    handleNetworkStatusChange() {
        const isOnline = navigator.onLine;

        if (isOnline) {
            this.elements.offlineIndicator.style.display = "none";
            this.showSuccess("Back online");
            this.syncNotes();
        } else {
            this.elements.offlineIndicator.style.display = "inline";
            this.showWarning("You are offline");
        }
    }

    /**
     * Toggle auto sync
     */
    toggleAutoSync() {
        const enabled = this.elements.autoSync.checked;
        this.noteService.setSyncEnabled(enabled);

        if (enabled) {
            this.showSuccess("Auto-sync enabled");
        } else {
            this.showWarning("Auto-sync disabled");
        }
    }

    /**
     * Highlight note in list
     */
    highlightNoteInList(noteId) {
        document.querySelectorAll(".note-item").forEach((item) => {
            item.classList.toggle("active", item.dataset.noteId === noteId);
        });
    }

    /**
     * Update sync info
     */
    updateSyncInfo() {
        const syncStatus = this.noteService.getSyncStatus();
        const lastSynced = localStorage.getItem("last_synced");

        this.elements.syncInfoText.textContent = lastSynced
            ? `Last synced: ${this.formatDate(lastSynced)}`
            : "Last synced: Never";

        this.elements.autoSync.checked = syncStatus.enabled;
    }

    /**
     * Set loading state
     */
    setLoading(loading) {
        this.isLoading = loading;

        const loadingIndicator = document.querySelector(".loading-indicator");
        if (loadingIndicator) {
            loadingIndicator.style.display = loading ? "block" : "none";
        }

        // Disable/enable buttons
        const buttons = document.querySelectorAll(".note-manager button");
        buttons.forEach((btn) => {
            if (loading) {
                btn.disabled = true;
                btn.classList.add("disabled");
            } else {
                btn.disabled = false;
                btn.classList.remove("disabled");
            }
        });
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        this.showNotification(message, "success");
    }

    /**
     * Show warning message
     */
    showWarning(message) {
        this.showNotification(message, "warning");
    }

    /**
     * Show error message
     */
    showError(message) {
        this.showNotification(message, "error");
    }

    /**
     * Show notification
     */
    showNotification(message, type = "info") {
        // Implement notification system
        console.log(`[${type.toUpperCase()}] ${message}`);

        // You can integrate with your existing notification system here
        // For now, we'll use a simple console log
    }

    /**
     * Utility: Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Utility: Truncate text
     */
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + "...";
    }

    /**
     * Utility: Format date
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            return "Today";
        } else if (diffDays === 1) {
            return "Yesterday";
        } else if (diffDays < 7) {
            return `${diffDays} days ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    /**
     * Utility: Extract text from rich content
     */
    extractTextFromRichContent(contentJson) {
        if (!contentJson || !contentJson.ops) return "";

        return contentJson.ops
            .map((op) => (typeof op.insert === "string" ? op.insert : ""))
            .join("")
            .trim();
    }

    /**
     * Destroy component
     */
    destroy() {
        this.noteService.destroy();

        // Remove event listeners
        window.removeEventListener(
            "notes-sync-conflicts",
            this.handleSyncConflicts.bind(this),
        );
        window.removeEventListener(
            "online",
            this.handleNetworkStatusChange.bind(this),
        );
        window.removeEventListener(
            "offline",
            this.handleNetworkStatusChange.bind(this),
        );

        // Remove UI
        const noteManager = document.getElementById("note-manager");
        if (noteManager) {
            noteManager.remove();
        }
    }
}

// Export for use
if (typeof module !== "undefined" && module.exports) {
    module.exports = NoteManager;
} else {
    window.NoteManager = NoteManager;
}
