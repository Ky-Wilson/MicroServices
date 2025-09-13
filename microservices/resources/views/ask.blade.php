@extends('layouts.master')

@section('content')
<div class="col-md-8 mx-auto">
    <div class="text-center mb-4">
        <h1 class="mb-3">
            <i class="fas fa-brain text-primary"></i> 
            Assistant IA Wikipédia
        </h1>
        <p class="lead text-muted">Posez votre question et obtenez une réponse intelligente et détaillée</p>
    </div>

    <!-- Formulaire principal -->
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-body p-4">
            <form action="{{ route('ask') }}" method="POST" id="questionForm">
                @csrf
                <div class="mb-4">
                    <label for="question" class="form-label fw-bold">Votre question :</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-question-circle"></i>
                        </span>
                        <input type="text" 
                               name="question" 
                               id="question"
                               class="form-control @error('question') is-invalid @enderror" 
                               placeholder="Ex: Qu'est-ce que Laravel ?" 
                               value="{{ old('question') }}" 
                               required
                               autocomplete="off">
                        <button type="submit" class="btn btn-primary px-4" id="searchBtn">
                            <span class="btn-text">
                                <i class="fas fa-search"></i> Rechercher
                            </span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin"></i> Recherche...
                            </span>
                        </button>
                    </div>
                    
                    @error('question')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    
                    <div class="form-text mt-2">
                        <small class="text-muted">
                            💡 <strong>Astuce :</strong> Soyez précis dans votre question pour obtenir des résultats plus pertinents
                        </small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Suggestions de questions -->
    <div class="card border-0 bg-light mb-4">
        <div class="card-body">
            <h5 class="card-title text-center mb-3">
                <i class="fas fa-lightbulb text-warning"></i> Exemples de questions
            </h5>
            
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="list-group list-group-flush">
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Qu'est-ce que l'intelligence artificielle ?">
                            <i class="fas fa-robot text-primary"></i> Qu'est-ce que l'intelligence artificielle ?
                        </button>
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Qui était Napoleon Bonaparte ?">
                            <i class="fas fa-crown text-warning"></i> Qui était Napoleon Bonaparte ?
                        </button>
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Comment fonctionne la photosynthèse ?">
                            <i class="fas fa-leaf text-success"></i> Comment fonctionne la photosynthèse ?
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="list-group list-group-flush">
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Où se trouve la tour Eiffel ?">
                            <i class="fas fa-map-marker-alt text-danger"></i> Où se trouve la tour Eiffel ?
                        </button>
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Quand a été créé Internet ?">
                            <i class="fas fa-globe text-info"></i> Quand a été créé Internet ?
                        </button>
                        <button type="button" class="list-group-item list-group-item-action border-0 suggestion-btn" 
                                data-question="Combien d'habitants en France ?">
                            <i class="fas fa-users text-secondary"></i> Combien d'habitants en France ?
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Types de questions supportées -->
    <div class="card border-0">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">
                <i class="fas fa-cogs"></i> Types de questions supportées
            </h5>
            
            <div class="row g-3">
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-book fa-2x text-primary mb-2"></i>
                        <h6>Définitions</h6>
                        <small class="text-muted">Qu'est-ce que... ? C'est quoi... ?</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-user fa-2x text-success mb-2"></i>
                        <h6>Biographies</h6>
                        <small class="text-muted">Qui est... ? Qui était... ?</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                        <h6>Dates & Époques</h6>
                        <small class="text-muted">Quand... ? En quelle année... ?</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-map fa-2x text-info mb-2"></i>
                        <h6>Géographie</h6>
                        <small class="text-muted">Où... ? Dans quel pays... ?</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-cog fa-2x text-danger mb-2"></i>
                        <h6>Fonctionnement</h6>
                        <small class="text-muted">Comment... ? De quelle manière... ?</small>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-box p-3 rounded bg-light">
                        <i class="fas fa-calculator fa-2x text-secondary mb-2"></i>
                        <h6>Quantités</h6>
                        <small class="text-muted">Combien... ? Quel nombre... ?</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historique des recherches récentes (si disponible) -->
    @if(session('recent_searches'))
        <div class="card border-0 mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-history"></i> Recherches récentes
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    @foreach(session('recent_searches') as $search)
                        <button type="button" class="btn btn-sm btn-outline-secondary suggestion-btn" 
                                data-question="{{ $search }}">
                            {{ $search }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.feature-box {
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.feature-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #dee2e6;
}

.suggestion-btn {
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.suggestion-btn:hover {
    transform: translateX(10px);
    background-color: #f8f9fa !important;
}

.input-group-lg .form-control {
    border-radius: 0;
}

.input-group-lg .input-group-text {
    border-radius: 0.375rem 0 0 0.375rem;
}

.input-group-lg .btn {
    border-radius: 0 0.375rem 0.375rem 0;
}

#question:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}

.btn-loading .fas {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
    .input-group-lg {
        flex-direction: column;
    }
    
    .input-group-lg .btn {
        border-radius: 0.375rem;
        margin-top: 10px;
    }
    
    .feature-box {
        margin-bottom: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionInput = document.getElementById('question');
    const form = document.getElementById('questionForm');
    const searchBtn = document.getElementById('searchBtn');
    const suggestionBtns = document.querySelectorAll('.suggestion-btn');
    
    // Auto-focus sur le champ de saisie
    questionInput.focus();
    
    // Gestion des suggestions de questions
    suggestionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const question = this.getAttribute('data-question');
            questionInput.value = question;
            questionInput.focus();
            
            // Animation de mise en évidence
            questionInput.classList.add('bg-light');
            setTimeout(() => {
                questionInput.classList.remove('bg-light');
            }, 1000);
        });
    });
    
    // Animation du bouton de recherche
    form.addEventListener('submit', function(e) {
        const btnText = searchBtn.querySelector('.btn-text');
        const btnLoading = searchBtn.querySelector('.btn-loading');
        
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');
        searchBtn.disabled = true;
    });
    
    // Validation en temps réel
    questionInput.addEventListener('input', function() {
        const value = this.value.trim();
        const isValid = value.length >= 3;
        
        searchBtn.disabled = !isValid;
        
        if (value.length > 0 && value.length < 3) {
            this.classList.add('is-invalid');
            if (!document.querySelector('.real-time-feedback')) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback real-time-feedback';
                feedback.textContent = 'Minimum 3 caractères requis';
                this.parentNode.appendChild(feedback);
            }
        } else {
            this.classList.remove('is-invalid');
            const feedback = document.querySelector('.real-time-feedback');
            if (feedback) {
                feedback.remove();
            }
        }
    });
    
    // Suggestions automatiques basées sur la saisie
    let suggestionTimeout;
    questionInput.addEventListener('input', function() {
        clearTimeout(suggestionTimeout);
        suggestionTimeout = setTimeout(() => {
            showAutoSuggestions(this.value);
        }, 300);
    });
    
    function showAutoSuggestions(query) {
        if (query.length < 2) return;
        
        // Suggestions simples basées sur les mots-clés
        const suggestions = {
            'qui': ['Qui était Einstein ?', 'Qui a inventé Internet ?', 'Qui est le président français ?'],
            'que': ['Qu\'est-ce que PHP ?', 'Qu\'est-ce que Laravel ?', 'Qu\'est-ce que l\'IA ?'],
            'quand': ['Quand a été créé Facebook ?', 'Quand a eu lieu la révolution française ?'],
            'où': ['Où se trouve Paris ?', 'Où est né Mozart ?'],
            'comment': ['Comment fonctionne un ordinateur ?', 'Comment faire du pain ?']
        };
        
        const firstWord = query.toLowerCase().split(' ')[0];
        if (suggestions[firstWord]) {
            // Ici vous pourriez afficher des suggestions dynamiques
            console.log('Suggestions disponibles:', suggestions[firstWord]);
        }
    }
    
    // Raccourci clavier pour soumettre (Ctrl+Enter)
    questionInput.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            form.submit();
        }
    });
    
    // Animation d'entrée pour les éléments
    const animateElements = document.querySelectorAll('.card, .feature-box');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});

// Fonction pour sauvegarder les recherches récentes
function saveRecentSearch(question) {
    let recent = JSON.parse(localStorage.getItem('recent_searches') || '[]');
    recent.unshift(question);
    recent = recent.slice(0, 5); // Garde seulement les 5 dernières
    localStorage.setItem('recent_searches', JSON.stringify(recent));
}

// Chargement des recherches récentes depuis localStorage
window.addEventListener('load', function() {
    const recent = JSON.parse(localStorage.getItem('recent_searches') || '[]');
    if (recent.length > 0) {
        // Ici vous pourriez afficher les recherches récentes
        console.log('Recherches récentes:', recent);
    }
});
</script>
@endsection