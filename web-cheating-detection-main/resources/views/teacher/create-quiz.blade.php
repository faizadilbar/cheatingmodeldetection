@extends('layouts.app')
@section('title','Create Quiz')
@section('page-title','Create Quiz')
@section('page-subtitle','AI-powered or manual quiz creation')

@section('sidebar-nav')
<span class="nav-section">Teacher Panel</span>
<a href="{{ route('teacher.dashboard') }}" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
<a href="{{ route('teacher.proctor.reports') }}" class="nav-item"><i class="fas fa-shield-halved"></i> Proctoring Reports</a>
@endsection

@section('topbar-actions')
<a href="{{ route('teacher.dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
@endsection

@push('styles')
<style>
.tab-bar { display:flex; gap:4px; background:#F1F5F9; padding:4px; border-radius:12px; margin-bottom:24px; }
.tab-btn { flex:1; padding:10px; border:none; border-radius:9px; font-size:13.5px; font-weight:600; cursor:pointer; transition:.2s; background:transparent; color:var(--text2); }
.tab-btn.active { background:#fff; color:var(--primary); box-shadow:0 2px 8px rgba(0,0,0,.08); }
.tab-panel { display:none; } .tab-panel.active { display:block; }

/* QUESTION CARD */
.q-card { background:#FFFFFF; border:1.5px solid var(--border); border-radius:12px; padding:16px; margin-bottom:12px; position:relative; box-shadow: 0 4px 16px rgba(61, 82, 160, 0.06); transition: all 0.2s ease; }
.q-card:hover { border-color: var(--primary-light); box-shadow: 0 8px 24px rgba(61, 82, 160, 0.12); transform: translateY(-2px); }
.q-card .q-num { position:absolute; top:-10px; left:14px; background:linear-gradient(135deg,var(--deep),var(--mid)); color:#fff; font-size:11px; font-weight:700; padding:2px 10px; border-radius:20px; }
.q-type-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; margin-bottom:10px; }
.q-mcq  { background:#EFF6FF; color:#2563EB; }
.q-short{ background:#ECFDF5; color:#059669; }
.q-fill { background:#FFF7ED; color:#C2410C; }

/* AI PROGRESS */
.ai-loader { text-align:center; padding:40px; display:none; }
.ai-loader .loader-ring { width:60px; height:60px; border:4px solid #E0E7FF; border-top-color:var(--primary); border-radius:50%; animation:spin .9s linear infinite; margin:0 auto 16px; }
.ai-steps { display:flex; flex-direction:column; gap:8px; margin-top:16px; max-width:300px; margin-left:auto; margin-right:auto; }
.ai-step { display:flex; align-items:center; gap:10px; font-size:13px; color:var(--text3); }
.ai-step.done { color:var(--green); } .ai-step.active { color:var(--primary); font-weight:600; }
.ai-step i { width:16px; }

/* MCQ OPTIONS */
.mcq-options { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:8px; }
.mcq-opt-row { display:flex; align-items:center; gap:8px; }
.mcq-opt-label { width:24px; height:24px; border-radius:6px; background:#E0E7FF; color:var(--primary); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

/* MODAL BACKDROP */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    animation: fadeIn 0.2s ease-out;
}

/* MODAL CARD */
.modal-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid var(--border);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: var(--text1);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text3);
    cursor: pointer;
    transition: 0.15s;
    padding: 0;
    line-height: 1;
}
.modal-close:hover {
    color: var(--primary);
}

.modal-body {
    padding: 24px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translateY(12px) scale(0.98); opacity: 0; }
    to { transform: translateY(0) scale(1); opacity: 1; }
}

.q-actions-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 6px;
}

.q-action-btn {
    border: none;
    background: #fff;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    color: var(--text3);
    transition: 0.2s;
    border: 1px solid var(--border);
}

.q-action-btn.edit:hover {
    background: #EFF6FF;
    color: #2563EB;
    border-color: #BFDBFE;
}

.q-action-btn.delete:hover {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #FCA5A5;
}
</style>
@endpush

@section('content')
<!-- QUIZ META FORM -->
<div class="card fade-in" id="metaCard">
    <div class="card-header"><h3><i class="fas fa-info-circle" style="color:var(--primary);margin-right:8px;"></i>Quiz Details</h3></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;">
            <div class="form-group" style="margin:0;">
                <label class="form-label">Quiz Name *</label>
                <input type="text" id="quizName" class="form-control" placeholder="e.g. Mid-Term Test" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Topic *</label>
                <input type="text" id="quizTopic" class="form-control" placeholder="e.g. NLP, Security, OOP" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Quiz Date *</label>
                <input type="date" id="quizDate" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Start Time *</label>
                <input type="time" id="startTime" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">End Time *</label>
                <input type="time" id="endTime" class="form-control" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Total Marks</label>
                <input type="number" id="totalMarks" class="form-control" placeholder="Auto-calculated">
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Difficulty</label>
                <select id="quizDifficulty" class="form-control form-select">
                    <option value="easy">Easy</option>
                    <option value="medium" selected>Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;display:flex;align-items:flex-end;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:13.5px;font-weight:600;color:var(--text2);">
                    <input type="checkbox" id="isPoll" style="width:17px;height:17px;accent-color:var(--primary);"> This is a Poll
                </label>
            </div>
        </div>
    </div>
</div>

<!-- TAB BAR -->
<div class="tab-bar fade-in" style="margin-top:20px;">
    <button class="tab-btn active" onclick="switchTab('ai')"><i class="fas fa-robot"></i> AI Generate</button>
    <button class="tab-btn" onclick="switchTab('manual')"><i class="fas fa-pen"></i> Manual</button>
</div>

<!-- AI TAB -->
<div class="tab-panel active fade-in" id="tab-ai">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-brain" style="color:var(--secondary);margin-right:8px;"></i>AI Question Generator</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Topic / Subject *</label>
                <input type="text" id="aiTopic" class="form-control" placeholder="e.g. Object Oriented Programming in Java" oninput="onTopicInput(this.value)">
            </div>

            <!-- PAST QUIZ SUGGESTIONS (shown below topic when matches found) -->
            <div id="pastSuggestionsPanel" style="display:none;margin-bottom:16px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <i class="fas fa-history" style="color:var(--primary);font-size:13px;"></i>
                    <span style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.8px;">Past Quizzes on Same Topic</span>
                    <span id="pastSuggestCount" class="badge badge-blue" style="font-size:10px;"></span>
                </div>
                <div id="pastSuggestionsList"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label"><i class="fas fa-list" style="color:var(--primary);margin-right:5px;"></i>MCQs</label>
                    <input type="number" id="mcqCount" class="form-control" placeholder="0" min="0" max="100" value="5">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label"><i class="fas fa-align-left" style="color:#10B981;margin-right:5px;"></i>Short Answers</label>
                    <input type="number" id="shortCount" class="form-control" placeholder="0" min="0" max="50" value="3">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label"><i class="fas fa-underline" style="color:#F59E0B;margin-right:5px;"></i>Fill Blanks</label>
                    <input type="number" id="fillCount" class="form-control" placeholder="0" min="0" max="50" value="2">
                </div>
            </div>
            <button class="btn btn-primary" onclick="generateAI()" id="generateBtn" style="width:100%;padding:13px;font-size:15px;">
                <i class="fas fa-magic"></i> Generate with AI
            </button>

            <div class="ai-loader" id="aiLoader">
                <div class="loader-ring"></div>
                <h3 style="font-family:'Bricolage Grotesque',system-ui,sans-serif;font-size:18px;font-weight:800;color:var(--text1);">Generating Questions…</h3>
                <p style="font-size:13px;color:var(--text3);margin-top:6px;">This may take up to 2 minutes. AI is crafting your questions.</p>
                <div class="ai-steps">
                    <div class="ai-step active" id="step1"><i class="fas fa-circle-notch fa-spin"></i> Sending to AI engine…</div>
                    <div class="ai-step" id="step2"><i class="fas fa-circle"></i> Generating MCQs…</div>
                    <div class="ai-step" id="step3"><i class="fas fa-circle"></i> Generating Short Answers…</div>
                    <div class="ai-step" id="step4"><i class="fas fa-circle"></i> Generating Fill Blanks…</div>
                    <div class="ai-step" id="step5"><i class="fas fa-circle"></i> Finalising…</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MANUAL TAB -->
<div class="tab-panel" id="tab-manual">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-pen" style="color:#F59E0B;margin-right:8px;"></i>Add Manual Questions</h3>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-sm btn-secondary" onclick="addManualQuestion('mcq')"><i class="fas fa-list"></i> + Add MCQ</button>
                <button class="btn btn-sm btn-secondary" onclick="addManualQuestion('short')"><i class="fas fa-align-left"></i> + Add Short Answer</button>
                <button class="btn btn-sm btn-secondary" onclick="addManualQuestion('fill')"><i class="fas fa-underline"></i> + Add Fill Blank</button>
            </div>
        </div>
        <div class="card-body">
            <div class="empty-state" style="padding: 24px;">
                <div class="empty-icon" style="background:#FEF3C7;color:#D97706;"><i class="fas fa-edit"></i></div>
                <h3 style="font-size:16px;">Create Quiz Manually</h3>
                <p style="max-width:360px;margin:8px auto;font-size:13px;color:var(--text3);">Click the buttons above to add questions. All manually added questions will appear below in the unified preview list where you can edit options, text, and answers.</p>
            </div>
        </div>
    </div>
</div>

<!-- QUESTIONS PREVIEW -->
<div class="card fade-in" id="questionsPreview" style="display:none;margin-top:20px;">
    <div class="card-header">
        <h3><i class="fas fa-check-circle" style="color:#10B981;margin-right:8px;"></i>Questions Ready <span id="qCount" class="badge badge-green"></span></h3>
        <button class="btn btn-sm btn-secondary" onclick="clearQuestions()"><i class="fas fa-trash"></i> Clear All</button>
    </div>
    <div class="card-body" id="questionsContainer"></div>
    <div style="padding:20px;border-top:1px solid var(--border);">
        <button class="btn btn-primary" onclick="saveQuiz()" id="saveBtn" style="min-width:160px;padding:12px 24px;font-size:15px;">
            <i class="fas fa-save"></i> Save Quiz
        </button>
    </div>
</div>

<!-- PAST QUIZ QUESTION PICKER MODAL -->
<div id="pastQPickerModal" class="modal-backdrop" style="display:none;">
    <div class="modal-card" style="max-width:680px;">
        <div class="modal-header">
            <h3><i class="fas fa-history" style="color:var(--primary);margin-right:8px;"></i>Select Questions from Past Quiz</h3>
            <button class="modal-close" onclick="closePastPicker()">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <div id="pastPickerQuizName" style="font-size:13.5px;font-weight:700;color:var(--text1);"></div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-sm btn-secondary" onclick="toggleSelectAllPast(true)">Select All</button>
                    <button class="btn btn-sm btn-secondary" onclick="toggleSelectAllPast(false)">Deselect All</button>
                </div>
            </div>
            <div id="pastPickerQList" style="max-height:55vh;overflow-y:auto;display:flex;flex-direction:column;gap:10px;"></div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;border-top:1px solid var(--border);background:#F8FAFC;border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
            <span id="pastPickerSelCount" style="font-size:13px;color:var(--text3);">0 selected</span>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-secondary" onclick="closePastPicker()">Cancel</button>
                <button class="btn btn-primary" id="addFromPastBtn" onclick="addSelectedPastQuestions()" disabled><i class="fas fa-plus"></i> Add to Quiz</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT QUESTION MODAL -->
<div id="editQuestionModal" class="modal-backdrop" style="display:none;">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class="fas fa-edit" style="color:var(--primary);margin-right:8px;"></i>Edit Question</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editQIndex">
            <div class="form-group">
                <label class="form-label" style="font-weight:700;">Question Text</label>
                <textarea id="editQText" class="form-control" rows="3" required placeholder="Enter question text..."></textarea>
            </div>
            
            <!-- Type Specific: MCQ -->
            <div id="editMcqContainer" style="display:none;margin-top:16px;">
                <label class="form-label" style="font-weight:700;margin-bottom:8px;">Options & Correct Answer</label>
                <p style="font-size:12px;color:var(--text3);margin-bottom:12px;margin-top:-6px;"><i class="fas fa-info-circle"></i> Select the radio button corresponding to the correct answer choice.</p>
                <div style="display:flex;flex-direction:column;gap:12px;">
                    <!-- A -->
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="radio" name="editMcqCorrect" value="A" id="radioOptA" style="width:18px;height:18px;accent-color:#10B981;cursor:pointer;">
                        <div class="mcq-opt-label">A</div>
                        <input type="text" id="editOptA" class="form-control" placeholder="Option A value" style="margin:0;">
                    </div>
                    <!-- B -->
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="radio" name="editMcqCorrect" value="B" id="radioOptB" style="width:18px;height:18px;accent-color:#10B981;cursor:pointer;">
                        <div class="mcq-opt-label">B</div>
                        <input type="text" id="editOptB" class="form-control" placeholder="Option B value" style="margin:0;">
                    </div>
                    <!-- C -->
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="radio" name="editMcqCorrect" value="C" id="radioOptC" style="width:18px;height:18px;accent-color:#10B981;cursor:pointer;">
                        <div class="mcq-opt-label">C</div>
                        <input type="text" id="editOptC" class="form-control" placeholder="Option C value" style="margin:0;">
                    </div>
                    <!-- D -->
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="radio" name="editMcqCorrect" value="D" id="radioOptD" style="width:18px;height:18px;accent-color:#10B981;cursor:pointer;">
                        <div class="mcq-opt-label">D</div>
                        <input type="text" id="editOptD" class="form-control" placeholder="Option D value" style="margin:0;">
                    </div>
                </div>
            </div>
            
            <!-- Type Specific: Short / Fill -->
            <div id="editNonMcqContainer" style="display:none;margin-top:16px;">
                <div class="form-group" style="margin:0;">
                    <label class="form-label" id="editAnswerLabel" style="font-weight:700;">Expected Answer / Keywords</label>
                    <input type="text" id="editCorrectAnswer" class="form-control" placeholder="Correct answer or keywords">
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;border-top:1px solid var(--border);background:#F8FAFC;border-bottom-left-radius:16px;border-bottom-right-radius:16px;">
            <button class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveEditedQuestion()"><i class="fas fa-check"></i> Save Changes</button>
        </div>
    </div>
</div>

<!-- QUIZ CODE MODAL -->
<div id="quizCodeModal" class="modal-backdrop" style="display:none;">
    <div class="modal-card" style="max-width: 400px; overflow: hidden; border-radius: 24px;">
        <!-- Header banner with gradient -->
        <div id="codeModalHeader" style="padding: 32px 24px; text-align: center; color: white; background: linear-gradient(135deg, var(--primary), var(--secondary));">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <i id="codeModalIcon" class="fas fa-check" style="font-size: 24px; color: white;"></i>
            </div>
            <h3 id="codeModalTitle" style="margin: 0 0 6px; font-size: 22px; font-weight: 800; color: white; border: none; padding: 0;">Quiz Saved!</h3>
            <p style="margin: 0; font-size: 13px; opacity: 0.9;">Share this code with students</p>
        </div>

        <div class="modal-body" style="padding: 28px 24px; text-align: center;">
            <label id="codeLabel" style="font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: var(--text3); display: block; margin-bottom: 10px;">Quiz Code</label>
            
            <!-- Code container -->
            <div onclick="copyQuizCode()" style="position: relative; background: #F8FAFC; border: 1.5px solid var(--border); border-radius: 14px; padding: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#F1F5F9'; this.style.borderColor='var(--primary)'" onmouseout="this.style.background='#F8FAFC'; this.style.borderColor='var(--border)'">
                <span id="displayQuizCode" style="font-family: monospace; font-size: 28px; font-weight: 800; letter-spacing: 4px; color: var(--text1);">ABCDEF</span>
                <i class="far fa-copy" style="position: absolute; right: 20px; color: var(--text3); font-size: 20px;"></i>
            </div>
            
            <small id="copyMessage" style="display: block; margin-top: 10px; font-size: 12px; color: var(--green); font-weight: 700; opacity: 0; transition: 0.2s;"><i class="fas fa-check-circle"></i> Copied to clipboard!</small>
        </div>

        <div class="modal-footer" style="padding: 16px 24px; text-align: center; background: #F8FAFC; border-top: 1px solid var(--border);">
            <button class="btn btn-primary" id="btnDoneRedirect" style="width: 100%; padding: 12px; font-size: 14.5px; font-weight: 700;">Done</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const courseId = {{ $courseId }};
let allQuestions = [];
let jobId = null;
let pollInterval = null;

// ── Past Quiz Suggestions ──
let pastSuggestDebounce = null;
let pastPickerQuestions = []; // full questions from the selected past quiz
let pastPickerSelectedIdx = new Set();
let allCourseQuizzes = null;   // cache: fetched once, filtered client-side
let isFetchingQuizzes = false;

document.addEventListener('DOMContentLoaded', function() {
    const isPollCheckbox = document.getElementById('isPoll');
    const mcqCountInput = document.getElementById('mcqCount');
    let previousMcqValue = mcqCountInput.value || 5;

    isPollCheckbox.addEventListener('change', function() {
        if (this.checked) {
            previousMcqValue = mcqCountInput.value;
            mcqCountInput.value = 100;
            mcqCountInput.disabled = true;
            mcqCountInput.style.background = '#F1F5F9';
            mcqCountInput.style.cursor = 'not-allowed';
            
            // Add hint text
            if (!document.getElementById('mcqPollHint')) {
                const hint = document.createElement('small');
                hint.id = 'mcqPollHint';
                hint.style.display = 'block';
                hint.style.fontSize = '11px';
                hint.style.color = 'var(--secondary)';
                hint.style.marginTop = '4px';
                hint.style.fontWeight = '600';
                hint.innerHTML = '<i class="fas fa-lock"></i> Fixed to 100 for polls';
                mcqCountInput.parentNode.appendChild(hint);
            }
        } else {
            mcqCountInput.value = previousMcqValue;
            mcqCountInput.disabled = false;
            mcqCountInput.style.background = '';
            mcqCountInput.style.cursor = '';
            
            // Remove hint text
            const hint = document.getElementById('mcqPollHint');
            if (hint) hint.remove();
        }
    });

    const quizTopicInput = document.getElementById('quizTopic');
    if (quizTopicInput) {
        quizTopicInput.addEventListener('input', function() {
            this.dataset.autosynced = 'false';
        });
    }
});

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach((b,i) => b.classList.toggle('active', (i===0&&tab==='ai')||(i===1&&tab==='manual')));
    document.querySelectorAll('.tab-panel').forEach((p,i) => p.classList.toggle('active', (i===0&&tab==='ai')||(i===1&&tab==='manual')));
}

// ── PAST QUIZ SUGGESTIONS ──────────────────────────────
function onTopicInput(val) {
    clearTimeout(pastSuggestDebounce);
    const q = val.trim();

    // Auto-sync to Quiz Details Topic field if empty or matches previous values
    const quizTopicInput = document.getElementById('quizTopic');
    if (quizTopicInput && (!quizTopicInput.value.trim() || quizTopicInput.dataset.autosynced === 'true')) {
        quizTopicInput.value = val;
        quizTopicInput.dataset.autosynced = 'true';
    }

    if (q.length < 3) {
        document.getElementById('pastSuggestionsPanel').style.display = 'none';
        return;
    }
    // If already cached, filter immediately without debounce
    if (allCourseQuizzes !== null) {
        filterAndShowSuggestions(q);
        return;
    }
    pastSuggestDebounce = setTimeout(() => loadAllCourseQuizzes(q), 400);
}

async function loadAllCourseQuizzes(topic) {
    if (isFetchingQuizzes) return;
    isFetchingQuizzes = true;
    try {
        const url = `{{ route('teacher.past-quizzes') }}?course_id=${courseId}`;
        console.log('[PastQuiz] Fetching:', url);
        const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        console.log('[PastQuiz] API response:', data);
        allCourseQuizzes = data.quizzes || [];
        console.log('[PastQuiz] Cached quizzes count:', allCourseQuizzes.length);
        filterAndShowSuggestions(topic);
    } catch(e) {
        console.error('[PastQuiz] Fetch failed:', e);
    } finally {
        isFetchingQuizzes = false;
    }
}

function filterAndShowSuggestions(topic) {
    if (!allCourseQuizzes || allCourseQuizzes.length === 0) {
        document.getElementById('pastSuggestionsPanel').style.display = 'none';
        return;
    }

    const q = topic.toLowerCase().trim();

    // ── Enrich API quizzes with localStorage topic data (like Flutter SQLite) ──
    const topicKey = `quizTopics_{{ $teacher['id'] ?? 0 }}_${courseId}`;
    const topicMap = JSON.parse(localStorage.getItem(topicKey) || '{}');

    const enriched = allCourseQuizzes.map(quiz => {
        const saved = topicMap[quiz.quiz_code];
        // Flutter uses quiz.quizName as "best guess" fallback for topic if not cached locally
        const t = saved ? saved.topic : (quiz.description || quiz.quiz_name || '').toLowerCase().trim();
        return { ...quiz, _topic: t };
    });

    // ── Strictly match by topic only ──
    let matched = enriched.filter(quiz => {
        return quiz._topic.includes(q);
    });

    if (matched.length === 0) {
        // Fallback: show all past quizzes in this course instead of showing blank,
        // so the teacher can easily pick any past quiz to reuse questions.
        renderPastSuggestions(enriched, q);
        return;
    }

    renderPastSuggestions(matched, null);
}


function renderPastNoMatch() {
    const panel = document.getElementById('pastSuggestionsPanel');
    const list  = document.getElementById('pastSuggestionsList');
    document.getElementById('pastSuggestCount').textContent = `0 found`;
    list.innerHTML = `
        <div style="font-size:12.5px;color:var(--text3);padding:12px 16px;background:rgba(0,0,0,0.02);border:1.5px dashed var(--border);border-radius:12px;text-align:center;">
            <i class="fas fa-search" style="margin-right:6px;"></i> No past quizzes found matching this topic.
        </div>`;
    panel.style.display = 'block';
}

function renderPastSuggestions(quizzes, note) {
    const panel = document.getElementById('pastSuggestionsPanel');
    const list  = document.getElementById('pastSuggestionsList');
    document.getElementById('pastSuggestCount').textContent =
        note ? `${quizzes.length} past quizzes (no "${note}" match)` : `${quizzes.length} found`;
    list.innerHTML = '';

    // Show note if showing all (no topic match)
    if (note) {
        const noteEl = document.createElement('div');
        noteEl.style.cssText = 'font-size:12px;color:#92400E;margin-bottom:10px;padding:8px 12px;background:#FEF3C7;border-radius:8px;border-left:3px solid #F59E0B;';
        noteEl.innerHTML = `<i class="fas fa-info-circle" style="margin-right:5px;color:#D97706;"></i>`
            + `No past quiz named "<b>${note}</b>" found — showing all ${quizzes.length} past quizzes. Select one to reuse its questions.`;
        list.appendChild(noteEl);
    }

    quizzes.forEach(q => {
        const card = document.createElement('div');
        card.style.cssText = 'background:#F8FAFC;border:1.5px solid var(--border);border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px;transition:.15s;';
        card.onmouseover = () => card.style.borderColor = 'var(--primary)';
        card.onmouseout  = () => card.style.borderColor = 'var(--border)';
        const totalQ = q.total_questions ?? '?';
        const safeName = (q.quiz_name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        card.innerHTML = `
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;font-size:13.5px;color:var(--text1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${q.quiz_name}</div>
                <div style="font-size:11px;color:var(--text3);margin-top:2px;">
                    <i class="fas fa-hashtag" style="margin-right:3px;"></i>${q.quiz_code}
                    &nbsp;·&nbsp;
                    <i class="fas fa-question-circle" style="margin-right:3px;"></i>${totalQ} questions
                    &nbsp;·&nbsp;
                    <i class="far fa-calendar" style="margin-right:3px;"></i>${q.quiz_date ?? ''}
                </div>
            </div>
            <button class="btn btn-sm btn-secondary" onclick="openPastPicker('${q.quiz_code}','${safeName}')">
                <i class="fas fa-list-check"></i> Select
            </button>`;
        list.appendChild(card);
    });
    panel.style.display = 'block';
}

async function openPastPicker(code, quizName) {
    document.getElementById('pastPickerQuizName').textContent = quizName;
    document.getElementById('pastPickerQList').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3);"><i class="fas fa-spinner fa-spin"></i> Loading questions…</div>';
    document.getElementById('pastQPickerModal').style.display = 'flex';
    pastPickerSelectedIdx.clear();
    updatePastPickerCount();

    try {
        const url = `{{ url('/teacher/past-quiz') }}/${code}/questions`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        pastPickerQuestions = data.questions || [];

        const list = document.getElementById('pastPickerQList');
        list.innerHTML = '';
        if (pastPickerQuestions.length === 0) {
            list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--text3);">No questions found</div>';
            return;
        }
        pastPickerQuestions.forEach((q, i) => {
            const type   = q.type ?? 'mcq';
            const typeCls = type === 'mcq' ? 'q-mcq' : type === 'short' ? 'q-short' : 'q-fill';
            const typeLbl = type === 'mcq' ? 'MCQ' : type === 'short' ? 'Short' : 'Fill Blank';
            const div = document.createElement('div');
            div.style.cssText = 'background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:14px 16px;display:flex;align-items:flex-start;gap:12px;cursor:pointer;transition:.15s;';
            div.setAttribute('data-idx', i);
            div.onclick = () => {
                const cb = div.querySelector('input[type=checkbox]');
                cb.checked = !cb.checked;
                if (cb.checked) pastPickerSelectedIdx.add(i);
                else pastPickerSelectedIdx.delete(i);
                div.style.borderColor = cb.checked ? 'var(--primary)' : 'var(--border)';
                div.style.background  = cb.checked ? '#EFF6FF' : '#fff';
                updatePastPickerCount();
            };
            div.innerHTML = `
                <input type="checkbox" style="margin-top:3px;width:16px;height:16px;accent-color:var(--primary);cursor:pointer;flex-shrink:0;" onclick="event.stopPropagation();" onchange="this.checked?pastPickerSelectedIdx.add(${i}):pastPickerSelectedIdx.delete(${i});this.closest('div[data-idx]').style.borderColor=this.checked?'var(--primary)':'var(--border)';this.closest('div[data-idx]').style.background=this.checked?'#EFF6FF':'#fff';updatePastPickerCount();">
                <div style="flex:1;">
                    <span class="q-type-badge ${typeCls}" style="margin-bottom:6px;">${typeLbl}</span>
                    <div style="font-size:13.5px;color:var(--text1);font-weight:600;line-height:1.4;">${q.question}</div>
                    ${type === 'mcq' ? `<div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:8px;font-size:12px;color:var(--text3);">
                        <span><b>A:</b> ${q.option_a ?? ''}</span><span><b>B:</b> ${q.option_b ?? ''}</span>
                        <span><b>C:</b> ${q.option_c ?? ''}</span><span><b>D:</b> ${q.option_d ?? ''}</span>
                    </div>` : `<div style="font-size:12px;color:var(--text3);margin-top:4px;"><b>Answer:</b> ${q.correct_answer ?? ''}</div>`}
                </div>`;
            list.appendChild(div);
        });
    } catch(e) {
        document.getElementById('pastPickerQList').innerHTML = '<div style="text-align:center;padding:20px;color:#EF4444;">Failed to load questions. Please try again.</div>';
    }
}

function toggleSelectAllPast(selectAll) {
    pastPickerSelectedIdx.clear();
    document.querySelectorAll('#pastPickerQList div[data-idx]').forEach(div => {
        const idx = parseInt(div.getAttribute('data-idx'));
        const cb  = div.querySelector('input[type=checkbox]');
        cb.checked = selectAll;
        div.style.borderColor = selectAll ? 'var(--primary)' : 'var(--border)';
        div.style.background  = selectAll ? '#EFF6FF' : '#fff';
        if (selectAll) pastPickerSelectedIdx.add(idx);
    });
    updatePastPickerCount();
}

function updatePastPickerCount() {
    const n = pastPickerSelectedIdx.size;
    document.getElementById('pastPickerSelCount').textContent = `${n} question${n !== 1 ? 's' : ''} selected`;
    document.getElementById('addFromPastBtn').disabled = n === 0;
}

function closePastPicker() {
    document.getElementById('pastQPickerModal').style.display = 'none';
    pastPickerQuestions = [];
    pastPickerSelectedIdx.clear();
}

function addSelectedPastQuestions() {
    const toAdd = [...pastPickerSelectedIdx].map(i => {
        const q    = pastPickerQuestions[i];
        const type = q.type ?? 'mcq';
        if (type === 'mcq') {
            return { type: 'mcq', question: q.question, option_a: q.option_a ?? '', option_b: q.option_b ?? '', option_c: q.option_c ?? '', option_d: q.option_d ?? '', correct_answer: q.correct_answer ?? 'A', _from_cache: true };
        }
        return { type, question: q.question, correct_answer: q.correct_answer ?? '', _from_cache: true };
    });

    // Skip duplicates (same question text already exists)
    const existingTexts = new Set(allQuestions.map(q => q.question.trim().toLowerCase()));
    const fresh = toAdd.filter(q => !existingTexts.has(q.question.trim().toLowerCase()));
    allQuestions.push(...fresh);

    closePastPicker();
    renderPreview();

    const skipped = toAdd.length - fresh.length;
    const msg = skipped > 0
        ? `Added ${fresh.length} questions (${skipped} duplicate${skipped > 1 ? 's' : ''} skipped)`
        : `${fresh.length} question${fresh.length !== 1 ? 's' : ''} added from past quiz!`;
    showToast(msg);
}

function showToast(msg) {
    let t = document.getElementById('toastMsg');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toastMsg';
        t.style.cssText = 'position:fixed;bottom:28px;right:28px;background:#1E293B;color:#fff;padding:12px 20px;border-radius:10px;font-size:13.5px;font-weight:600;z-index:99999;box-shadow:0 8px 24px rgba(0,0,0,.2);transition:opacity .3s;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._hide);
    t._hide = setTimeout(() => t.style.opacity = '0', 3000);
}


async function generateAI() {
    const topic = document.getElementById('aiTopic').value.trim();
    if (!topic) { alert('Please enter a topic'); return; }

    const mcq   = parseInt(document.getElementById('mcqCount').value) || 0;
    const short = parseInt(document.getElementById('shortCount').value) || 0;
    const fill  = parseInt(document.getElementById('fillCount').value) || 0;
    if (!mcq && !short && !fill) { alert('Enter at least one question count'); return; }

    document.getElementById('generateBtn').style.display = 'none';
    document.getElementById('aiLoader').style.display = 'block';
    setStep(1);

    try {
        const res = await ajaxPost('{{ route("teacher.quiz.generate-ai") }}', {
            topic, difficulty: document.getElementById('quizDifficulty').value,
            is_poll: document.getElementById('isPoll').checked,
            categories: { mcqs: mcq, short_questions: short, fill_blanks: fill }
        });

        if (res.job_id) {
            jobId = res.job_id;
            setStep(2);
            pollInterval = setInterval(checkStatus, 4000);
        } else {
            // Direct response (no job)
            if (res.questions) processQuestions(res.questions);
            else { alert('AI generation failed'); resetAI(); }
        }
    } catch(e) { alert('Error: ' + e.message); resetAI(); }
}

async function checkStatus() {
    try {
        const res = await fetch(`{{ route("teacher.quiz.generation-status", ":id") }}`.replace(':id', jobId)).then(r=>r.json());
        if (!res.status) return;
        const data = res.data;
        if (data.status === 'processing') {
            if (data.summary) {
                if (data.summary.mcqs > 0) setStep(3);
                if (data.summary.short_questions > 0) setStep(4);
                if (data.summary.fill_blanks > 0) setStep(5);
            }
        } else if (data.status === 'completed') {
            clearInterval(pollInterval);
            processQuestions(data.questions);
        } else if (data.status === 'failed') {
            clearInterval(pollInterval);
            alert('AI generation failed: ' + (data.message || 'Unknown error'));
            resetAI();
        }
    } catch(e) {}
}

function processQuestions(qData) {
    // Keep already-selected past questions (from cache) and manual questions at the top
    allQuestions = allQuestions.filter(q => q._from_cache === true || q._is_manual === true);

    const mcqs   = qData.mcqs            || [];
    const shorts  = qData.short_questions || [];
    const fills   = qData.fill_blanks     || [];

    // Append new AI questions below them
    mcqs.forEach(q   => allQuestions.push({ type:'mcq',   question:q.question, option_a:q.option_a, option_b:q.option_b, option_c:q.option_c, option_d:q.option_d, correct_answer:q.correct_answer }));
    shorts.forEach(q  => allQuestions.push({ type:'short', question:q.question, correct_answer:q.correct_answer }));
    fills.forEach(q   => allQuestions.push({ type:'fill',  question:q.question, correct_answer:q.correct_answer }));

    renderPreview();
    document.getElementById('aiLoader').style.display = 'none';
    document.getElementById('generateBtn').style.display = 'block';
}

function setStep(n) {
    for (let i=1;i<=5;i++) {
        const el = document.getElementById('step'+i);
        if (!el) continue;
        if (i < n) { el.className='ai-step done'; el.querySelector('i').className='fas fa-check-circle'; }
        else if (i === n) { el.className='ai-step active'; el.querySelector('i').className='fas fa-circle-notch fa-spin'; }
        else { el.className='ai-step'; el.querySelector('i').className='fas fa-circle'; }
    }
}

function resetAI() {
    document.getElementById('aiLoader').style.display = 'none';
    document.getElementById('generateBtn').style.display = 'block';
    setStep(1);
}

// ── MANUAL ────────────────────────────────────────────
// ── MANUAL ────────────────────────────────────────────
function addManualQuestion(type) {
    const newQ = {
        type: type,
        question: 'New question text',
        correct_answer: type === 'mcq' ? 'A' : '',
        _is_manual: true
    };
    if (type === 'mcq') {
        newQ.option_a = 'Option A';
        newQ.option_b = 'Option B';
        newQ.option_c = 'Option C';
        newQ.option_d = 'Option D';
    }
    
    allQuestions.push(newQ);
    renderPreview();
    
    // Open the edit modal on it immediately so the user can type the question
    openEditModal(allQuestions.length - 1);
}

// ── EDIT & DELETE QUESTION ACTIONS ─────────────────────
function openEditModal(index) {
    const q = allQuestions[index];
    if (!q) return;

    document.getElementById('editQIndex').value = index;
    document.getElementById('editQText').value = q.question || '';
    
    if (q.type === 'mcq' || q.type === 'mcqs') {
        document.getElementById('editMcqContainer').style.display = 'block';
        document.getElementById('editNonMcqContainer').style.display = 'none';
        
        document.getElementById('editOptA').value = q.option_a || '';
        document.getElementById('editOptB').value = q.option_b || '';
        document.getElementById('editOptC').value = q.option_c || '';
        document.getElementById('editOptD').value = q.option_d || '';
        
        // Match correct radio choice
        const correct = (q.correct_answer || 'A').toUpperCase();
        const radio = document.querySelector(`input[name="editMcqCorrect"][value="${correct}"]`);
        if (radio) radio.checked = true;
    } else {
        document.getElementById('editMcqContainer').style.display = 'none';
        document.getElementById('editNonMcqContainer').style.display = 'block';
        document.getElementById('editCorrectAnswer').value = q.correct_answer || '';
        
        const answerLabel = document.getElementById('editAnswerLabel');
        if (q.type === 'short' || q.type === 'short_questions') {
            answerLabel.textContent = 'Expected Keywords (comma separated)';
        } else {
            answerLabel.textContent = 'Exact Correct Answer';
        }
    }
    
    document.getElementById('editQuestionModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editQuestionModal').style.display = 'none';
}

function saveEditedQuestion() {
    const idx = parseInt(document.getElementById('editQIndex').value);
    if (isNaN(idx) || !allQuestions[idx]) return;
    
    const text = document.getElementById('editQText').value.trim();
    if (!text) { alert('Question text is required'); return; }
    
    const q = allQuestions[idx];
    q.question = text;
    
    if (q.type === 'mcq' || q.type === 'mcqs') {
        q.option_a = document.getElementById('editOptA').value.trim();
        q.option_b = document.getElementById('editOptB').value.trim();
        q.option_c = document.getElementById('editOptC').value.trim();
        q.option_d = document.getElementById('editOptD').value.trim();
        
        const checkedRadio = document.querySelector('input[name="editMcqCorrect"]:checked');
        q.correct_answer = checkedRadio ? checkedRadio.value : 'A';
    } else {
        q.correct_answer = document.getElementById('editCorrectAnswer').value.trim();
    }
    
    closeEditModal();
    renderPreview();
}

function deleteQuestion(index) {
    if (confirm('Are you sure you want to delete this question?')) {
        allQuestions.splice(index, 1);
        renderPreview();
    }
}

// ── PREVIEW ────────────────────────────────────────────
function renderPreview() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';
    
    if (allQuestions.length === 0) {
        document.getElementById('questionsPreview').style.display = 'none';
        return;
    }
    
    document.getElementById('qCount').textContent = allQuestions.length + ' questions';

    allQuestions.forEach((q, i) => {
        const type = q.type;
        const typeLabel = (type==='mcq'||type==='mcqs')?'MCQ':(type==='short'||type==='short_questions')?'Short Answer':'Fill Blank';
        const typeClass = 'q-' + (type === 'mcqs' ? 'mcq' : type === 'short_questions' ? 'short' : type);
        let opts = '';
        
        if (type === 'mcq' || type === 'mcqs') {
            opts = `<div class="mcq-options" style="margin-top:8px;">
                ${['A','B','C','D'].map(l => {
                    const val = q['option_'+l.toLowerCase()];
                    const isCorrect = q.correct_answer === l;
                    return val ? `<div style="padding:6px 10px;border-radius:8px;font-size:12.5px;background:${isCorrect?'#ECFDF5':'#F8FAFC'};border:1.5px solid ${isCorrect?'#10B981':'var(--border)'};color:${isCorrect?'#065F46':'var(--text2)'};">
                        <strong>${l}.</strong> ${val} ${isCorrect?'<i class="fas fa-check-circle" style="color:#10B981;float:right;"></i>':''}
                    </div>` : '';
                }).join('')}
            </div>`;
        } else {
            opts = `<div style="margin-top:8px;padding:8px 12px;background:#F0FDF4;border-radius:8px;font-size:12.5px;color:#065F46;"><i class="fas fa-key" style="margin-right:6px;"></i>${q.correct_answer}</div>`;
        }
        
        container.innerHTML += `<div class="q-card" style="padding-right: 80px;">
            <span class="q-num">Q${i+1}</span>
            <span class="q-type-badge ${typeClass}">${typeLabel}</span>
            
            <!-- Edit & Delete Action Buttons -->
            <div class="q-actions-btn">
                <button class="q-action-btn edit" title="Edit Question" onclick="openEditModal(${i})">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <button class="q-action-btn delete" title="Delete Question" onclick="deleteQuestion(${i})">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            
            <div style="font-size:13.5px;font-weight:600;color:var(--text1);margin:6px 0;">${q.question}</div>
            ${opts}
        </div>`;
    });

    document.getElementById('questionsPreview').style.display = 'block';
}

function clearQuestions() {
    if (confirm('Are you sure you want to clear all questions?')) {
        allQuestions = [];
        renderPreview();
    }
}

// ── SAVE QUIZ ──────────────────────────────────────────
async function saveQuiz() {
    if (allQuestions.length === 0) { alert('Add or generate at least one question'); return; }

    const name  = document.getElementById('quizName').value.trim();
    const topic = document.getElementById('quizTopic').value.trim();
    const date  = document.getElementById('quizDate').value;
    const start = document.getElementById('startTime').value;
    const end   = document.getElementById('endTime').value;

    if (!name || !topic || !date || !start || !end) { alert('Please fill all quiz details including Topic'); return; }

    const btn = document.getElementById('saveBtn');
    btn.innerHTML = '<div class="spinner"></div>';
    btn.disabled  = true;

    try {
        const quizTopic = document.getElementById('quizTopic').value.trim()
                       || document.getElementById('aiTopic').value.trim();
        const res = await ajaxPost('{{ route("teacher.quiz.save") }}', {
            quiz_name:    name,
            course_id:    courseId,
            quiz_date:    date,
            start_time:   start,
            end_time:     end,
            total_marks:  document.getElementById('totalMarks').value || null,
            difficulty:   document.getElementById('quizDifficulty').value,
            is_poll:      document.getElementById('isPoll').checked,
            topic:        quizTopic,
            description:  quizTopic,
            questions:    allQuestions,
        });

        if (res.status && res.quiz_code) {
            // ── Save topic to localStorage (mirrors Flutter's local SQLite caching) ──
            const topicKey = `quizTopics_{{ $teacher['id'] ?? 0 }}_${courseId}`;
            let topicMap = JSON.parse(localStorage.getItem(topicKey) || '{}');
            topicMap[res.quiz_code] = {
                quiz_code: res.quiz_code,
                quiz_name: name,
                topic:     quizTopic.toLowerCase().trim(),
                quiz_date: date,
            };
            localStorage.setItem(topicKey, JSON.stringify(topicMap));

            // Customize modal headers/colors based on poll status
            const isPoll = res.is_poll || document.getElementById('isPoll').checked;
            const header = document.getElementById('codeModalHeader');
            const icon = document.getElementById('codeModalIcon');
            const title = document.getElementById('codeModalTitle');
            const label = document.getElementById('codeLabel');

            if (isPoll) {
                header.style.background = 'linear-gradient(135deg, #6A1B9A, #9C27B0)';
                icon.className = 'fas fa-poll';
                title.textContent = 'Poll Saved!';
                label.textContent = 'Poll Code';
            } else {
                header.style.background = 'linear-gradient(135deg, var(--primary), var(--secondary))';
                icon.className = 'fas fa-check';
                title.textContent = 'Quiz Saved!';
                label.textContent = 'Quiz Code';
            }

            // Set code text
            document.getElementById('displayQuizCode').textContent = res.quiz_code;

            // Bind Done Redirect button
            document.getElementById('btnDoneRedirect').onclick = function() {
                window.location = '{{ route("teacher.course.quizzes", ":cid") }}'.replace(':cid', courseId);
            };

            // Invalidate cache so next topic search re-fetches fresh list
            allCourseQuizzes = null;

            // Show Code modal
            document.getElementById('quizCodeModal').style.display = 'flex';
        } else {
            alert('Error: ' + (res.message || 'Failed to save'));
            btn.innerHTML = '<i class="fas fa-save"></i> Save Quiz';
            btn.disabled = false;
        }
    } catch(e) {
        alert('Error: ' + e.message);
        btn.innerHTML = '<i class="fas fa-save"></i> Save Quiz';
        btn.disabled = false;
    }
}

// ── COPY TO CLIPBOARD HELPER ────────────────────────────
function copyQuizCode() {
    const code = document.getElementById('displayQuizCode').textContent;
    if (!code) return;
    
    navigator.clipboard.writeText(code).then(() => {
        const copyMsg = document.getElementById('copyMessage');
        copyMsg.style.opacity = '1';
        setTimeout(() => {
            copyMsg.style.opacity = '0';
        }, 2000);
    }).catch(err => {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = code;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            const copyMsg = document.getElementById('copyMessage');
            copyMsg.style.opacity = '1';
            setTimeout(() => {
                copyMsg.style.opacity = '0';
            }, 2000);
        } catch (e) {}
        document.body.removeChild(textarea);
    });
}
</script>
@endpush
