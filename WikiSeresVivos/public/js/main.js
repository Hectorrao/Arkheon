document.addEventListener('DOMContentLoaded', function() {
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    window.handleSearch = function(event) {
        let searchInput = document.getElementById('searchInput');
        
        if (event && event.target) {
            const button = event.target.closest('#searchButton');
            if (button) {
                const parentContainer = button.parentElement;
                const nearbyInput = parentContainer.querySelector('.search-input');
                if (nearbyInput) {
                    searchInput = nearbyInput;
                }
            }
        }
        
        if (!searchInput) return;
        
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = 'search.php?q=' + encodeURIComponent(query);
        }
    };

    function setupAutocomplete(inputElement, suggestionsElement) {
        if (!inputElement || !suggestionsElement) return;

        inputElement.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 1) {
                suggestionsElement.hidden = true;
                return;
            }

            fetch('autocomplete.php?q=' + encodeURIComponent(query))
                .then(function(response) { 
                    return response.json(); 
                })
                .then(function(data) {
                    if (!data.length) {
                        suggestionsElement.hidden = true;
                        return;
                    }

                    suggestionsElement.innerHTML = data.map(function(item) {
                        var nombreResaltado = escapeHtml(item.nombre);
                        var cientificoResaltado = escapeHtml(item.nombre_cientifico);
                        
                        var queryLower = query.toLowerCase();
                        var nombreLower = item.nombre.toLowerCase();
                        var cientificoLower = item.nombre_cientifico.toLowerCase();
                        
                        if (nombreLower.indexOf(queryLower) === 0) {
                            var parteCoincidente = item.nombre.substring(0, query.length);
                            var parteRestante = item.nombre.substring(query.length);
                            nombreResaltado = '<strong>' + escapeHtml(parteCoincidente) + '</strong>' + escapeHtml(parteRestante);
                        }
                        
                        if (cientificoLower.indexOf(queryLower) === 0) {
                            var parteCoincidenteC = item.nombre_cientifico.substring(0, query.length);
                            var parteRestanteC = item.nombre_cientifico.substring(query.length);
                            cientificoResaltado = '<strong>' + escapeHtml(parteCoincidenteC) + '</strong>' + escapeHtml(parteRestanteC);
                        }
                        
                        var imagenHtml = '';
                        if (item.imagen) {
                            imagenHtml = '<img src="/img/portadas/' + escapeHtml(item.imagen) + '" alt="' + escapeHtml(item.nombre) + '" class="suggestion-img">';
                        }
                        
                        return '<div class="suggestion-item" data-id="' + item.id + '">' +
                            imagenHtml +
                            '<div class="suggestion-text">' +
                                '<span class="suggestion-nombre">' + nombreResaltado + '</span>' +
                                '<span class="suggestion-cientifico">' + cientificoResaltado + '</span>' +
                            '</div>' +
                            '</div>';
                    }).join('');
                    
                    suggestionsElement.hidden = false;
                    
                    document.querySelectorAll('.suggestion-item').forEach(function(el) {
                        el.removeEventListener('click', handleSuggestionClick);
                        el.addEventListener('click', handleSuggestionClick);
                    });
                })
                .catch(function(error) { 
                    console.error('Error en autocompletado:', error); 
                });
        });

        function handleSuggestionClick(e) {
            const el = e.currentTarget;
            window.location.href = 'animal.php?id=' + el.dataset.id;
        }

        inputElement.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const query = this.value.trim();
                if (query) {
                    window.location.href = 'search.php?q=' + encodeURIComponent(query);
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (!inputElement.contains(e.target) && !suggestionsElement.contains(e.target)) {
                suggestionsElement.hidden = true;
            }
        });
    }

    function setupAllAutocompletes() {
        const mainSearchInput = document.getElementById('searchInput');
        const mainSuggestions = document.getElementById('suggestions');
        if (mainSearchInput && mainSuggestions) {
            setupAutocomplete(mainSearchInput, mainSuggestions);
        }

        const topbarSearchInputs = document.querySelectorAll('.topbar-search .search-input');
        topbarSearchInputs.forEach(function(input) {
            let parentContainer = input.closest('.topbar-search');
            let existingSuggestions = parentContainer.querySelector('.suggestions-box');
            
            if (!existingSuggestions) {
                const newSuggestionsBox = document.createElement('div');
                newSuggestionsBox.id = 'suggestions-topbar-' + Math.random().toString(36).substr(2, 5);
                newSuggestionsBox.className = 'suggestions-box';
                newSuggestionsBox.hidden = true;
                parentContainer.appendChild(newSuggestionsBox);
                setupAutocomplete(input, newSuggestionsBox);
            } else {
                setupAutocomplete(input, existingSuggestions);
            }
        });
    }

    function setupAllSearchButtons() {
        const allSearchButtons = document.querySelectorAll('#searchButton');
        allSearchButtons.forEach(button => {
            button.removeEventListener('click', window.handleSearch);
            button.addEventListener('click', window.handleSearch);
        });
    }

    setupAllAutocompletes();
    setupAllSearchButtons();

    const observer = new MutationObserver(function() {
        setupAllSearchButtons();
        setupAllAutocompletes();
    });
    observer.observe(document.body, { childList: true, subtree: true });

    const chips = document.querySelectorAll('.epoch-chip');
    const epochChipsContainers = document.querySelectorAll('.epoch-chips');
    const hiddenPeriodo = document.getElementById('selected_periodo');
    const hiddenEra = document.getElementById('selected_era');
    const submitBtn = document.getElementById('submit_epoch');
    const epochNameSpan = document.getElementById('epoch_name');
    const epochLabelSpan = document.getElementById('epoch_label');
    const epochTitle = document.getElementById('epoch-axis-title');
    const epochTrack = document.getElementById('epoch-axis-track');
    const epochArrow = document.getElementById('epoch-axis-arrow');
    const epochBody = document.querySelector('.epoch-axis-body');

    const GROUP_CONFIG = {
        periodo: { title: 'EXPLORAR POR PERÍODO GEOLÓGICO', label: 'Período seleccionado:', arrowLabel: 'Ver eras geológicas' },
        era:     { title: 'EXPLORAR POR ERA GEOLÓGICA',     label: 'Era seleccionada:',     arrowLabel: 'Ver períodos geológicos' }
    };

    let currentGroup = 'periodo';

    function updateEpochInertness() {
        if (!epochTrack) return;
        Array.from(epochTrack.children).forEach(function(panel, i) {
            const isActive = (currentGroup === 'periodo' && i === 0) || (currentGroup === 'era' && i === 1);
            if (isActive) {
                panel.removeAttribute('inert');
                panel.removeAttribute('aria-hidden');
            } else {
                panel.setAttribute('inert', '');
                panel.setAttribute('aria-hidden', 'true');
            }
        });
    }

    function switchEpochGroup(group) {
        currentGroup = group;

        if (epochTrack) epochTrack.classList.toggle('show-eras', group === 'era');
        if (epochBody) epochBody.classList.toggle('is-era', group === 'era');
        if (epochArrow) epochArrow.setAttribute('aria-label', GROUP_CONFIG[group].arrowLabel);
        if (epochTitle) epochTitle.textContent = GROUP_CONFIG[group].title;
        if (epochLabelSpan) epochLabelSpan.textContent = GROUP_CONFIG[group].label;

        updateEpochInertness();
    }

    if (epochArrow) {
        epochArrow.addEventListener('click', function() {
            switchEpochGroup(currentGroup === 'periodo' ? 'era' : 'periodo');
        });
    }

    if (chips.length > 0) {
        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                const container = this.closest('.epoch-chips');
                const group = container ? container.dataset.type : 'periodo';
                const groupChips = document.querySelectorAll('.epoch-chips[data-type="' + group + '"] .epoch-chip');

                groupChips.forEach(function(c) {
                    c.classList.remove('active');
                });
                this.classList.add('active');

                const key = this.dataset.key;
                const label = this.dataset.label;

                if (group === 'era') {
                    if (hiddenEra) hiddenEra.value = key;
                    if (hiddenPeriodo) hiddenPeriodo.value = '';
                } else {
                    if (hiddenPeriodo) hiddenPeriodo.value = key;
                    if (hiddenEra) hiddenEra.value = '';
                }

                if (epochNameSpan) epochNameSpan.textContent = label;
                if (epochLabelSpan) epochLabelSpan.textContent = GROUP_CONFIG[group].label;
                if (submitBtn) submitBtn.disabled = false;
            });
        });

        document.addEventListener('click', function(e) {
            let clickedInsideChips = false;
            epochChipsContainers.forEach(function(container) {
                if (container.contains(e.target)) clickedInsideChips = true;
            });
            const clickedSubmit = submitBtn && submitBtn.contains(e.target);
            const clickedArrow = epochArrow && epochArrow.contains(e.target);

            if (!clickedInsideChips && !clickedSubmit && !clickedArrow) {
                chips.forEach(function(c) {
                    c.classList.remove('active');
                });
                if (hiddenPeriodo) hiddenPeriodo.value = '';
                if (hiddenEra) hiddenEra.value = '';
                if (epochNameSpan) epochNameSpan.textContent = 'Ninguno';
                if (epochLabelSpan) epochLabelSpan.textContent = GROUP_CONFIG[currentGroup].label;
                if (submitBtn) submitBtn.disabled = true;
            }
        });

        var urlParams = new URLSearchParams(window.location.search);
        var periodoUrl = urlParams.get('periodo');
        var eraUrl = urlParams.get('era');

        if (eraUrl) {
            switchEpochGroup('era');
            var eraChip = document.querySelector('.epoch-chips[data-type="era"] .epoch-chip[data-key="' + eraUrl + '"]');
            if (eraChip) eraChip.click();
        } else if (periodoUrl) {
            var periodoChip = document.querySelector('.epoch-chips[data-type="periodo"] .epoch-chip[data-key="' + periodoUrl + '"]');
            if (periodoChip) periodoChip.click();
        }

        updateEpochInertness();
    }
});