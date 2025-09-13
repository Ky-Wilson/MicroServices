@extends('layouts.master')

@section('content')
<div class="col-md-10 mx-auto">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Résultat pour : <small class="text-muted">{{ $question }}</small></h1>
        
        @if(isset($confidence) && $confidence > 0)
            <div class="badge {{ $confidence > 80 ? 'bg-success' : ($confidence > 50 ? 'bg-warning' : 'bg-secondary') }}">
                Confiance : {{ round($confidence) }}%
            </div>
        @endif
    </div>

    @if(!empty($title))
        <div class="row mb-4">
            <div class="col-md-8">
                <p><strong>Article trouvé :</strong> {{ $title }}</p>
                
                @if(isset($questionType) && $questionType !== 'general')
                    <p><small class="text-muted">Type de question détecté : 
                        <span class="badge bg-info">{{ ucfirst($questionType) }}</span>
                    </small></p>
                @endif
            </div>
            
            @if(!empty($thumbnail))
                <div class="col-md-4">
                    <img src="{{ $thumbnail }}" alt="{{ $title }}" class="img-fluid rounded shadow-sm" style="max-height: 200px;">
                </div>
            @endif
        </div>
    @endif

    <!-- Réponse principale -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Réponse</h5>
        </div>
        <div class="card-body">
            <div class="answer-content">
                {!! nl2br(e($answer)) !!}
            </div>
        </div>
    </div>

    <!-- Informations supplémentaires en onglets -->
    @if(!empty($infobox) || !empty($sections) || !empty($relatedTopics))
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="infoTabs" role="tablist">
                    @if(!empty($infobox))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="infobox-tab" data-bs-toggle="tab" data-bs-target="#infobox" type="button" role="tab">
                                <i class="fas fa-info-circle"></i> Fiche technique
                            </button>
                        </li>
                    @endif
                    
                    @if(!empty($sections))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ empty($infobox) ? 'active' : '' }}" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab">
                                <i class="fas fa-list"></i> Sections détaillées
                            </button>
                        </li>
                    @endif
                    
                    @if(!empty($relatedTopics))
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ empty($infobox) && empty($sections) ? 'active' : '' }}" id="related-tab" data-bs-toggle="tab" data-bs-target="#related" type="button" role="tab">
                                <i class="fas fa-link"></i> Sujets connexes
                            </button>
                        </li>
                    @endif
                </ul>
            </div>
            
            <div class="tab-content" id="infoTabsContent">
                @if(!empty($infobox))
                    <div class="tab-pane fade show active" id="infobox" role="tabpanel">
                        <div class="card-body">
                            <div class="row">
                                @foreach($infobox as $key => $value)
                                    <div class="col-md-6 mb-2">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }} :</strong>
                                        <span class="text-muted">{{ $value }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(!empty($sections))
                    <div class="tab-pane fade {{ empty($infobox) ? 'show active' : '' }}" id="sections" role="tabpanel">
                        <div class="card-body">
                            <div class="accordion" id="sectionsAccordion">
                                @foreach($sections as $index => $section)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading{{ $index }}">
                                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}">
                                                {{ $section['title'] }}
                                            </button>
                                        </h2>
                                        <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#sectionsAccordion">
                                            <div class="accordion-body">
                                                {{ substr($section['content'], 0, 500) }}{{ strlen($section['content']) > 500 ? '...' : '' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
                
                @if(!empty($relatedTopics))
                    <div class="tab-pane fade {{ empty($infobox) && empty($sections) ? 'show active' : '' }}" id="related" role="tabpanel">
                        <div class="card-body">
                            <p class="text-muted mb-3">Sujets en relation avec votre recherche :</p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($relatedTopics as $topic)
                                    <span class="badge bg-light text-dark border px-3 py-2">
                                        <i class="fas fa-tag"></i> {{ $topic }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Images supplémentaires -->
    @if(!empty($images) && count($images) > 1)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-images"></i> Images</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach(array_slice($images, 1) as $image)
                        <div class="col-md-4">
                            <img src="{{ $image }}" alt="Image liée à {{ $title }}" class="img-fluid rounded shadow-sm">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Actions et liens -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            @if(!empty($pageUrl))
                <a href="{{ $pageUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Lire l'article complet
                </a>
            @endif
        </div>
        
        <div>
            <a href="{{ route('ask.form') }}" class="btn btn-secondary">
                <i class="fas fa-search"></i> Nouvelle recherche
            </a>
            
            <button onclick="window.print()" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>
</div>

<style>
.answer-content {
    font-size: 1.1rem;
    line-height: 1.6;
}

.answer-content strong {
    color: #0066cc;
}

.badge {
    font-size: 0.9rem;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f3ff;
    color: #0066cc;
}

.tab-pane {
    min-height: 200px;
}

@media print {
    .btn, .nav-tabs, .accordion-button {
        display: none !important;
    }
    
    .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Amélioration de l'expérience utilisateur avec des tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    
    // Animation douce pour les onglets
    document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(button => {
        button.addEventListener('shown.bs.tab', function(e) {
            const targetPane = document.querySelector(e.target.getAttribute('data-bs-target'));
            targetPane.style.opacity = '0';
            setTimeout(() => {
                targetPane.style.transition = 'opacity 0.3s ease-in-out';
                targetPane.style.opacity = '1';
            }, 50);
        });
    });
});
</script>
@endsection