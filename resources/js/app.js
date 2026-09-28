import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import {
    Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, CalendarDays,
    ChartNoAxesColumn, ChevronDown, CircleCheck, CircleDotDashed, CircleHelp,
    ClipboardCheck, Clock3, Construction, createIcons, FileClock, Filter, GraduationCap,
    LayoutDashboard, ListChecks, LogIn, LogOut, Menu, Moon, NotebookTabs, PanelLeftClose,
    Pencil, Play, Plus, Save, Search, Settings, Shield, ShieldCheck, SlidersHorizontal, Sparkles,
    Sun, Trash2, TrendingUp, Trophy, UserPlus, UserRound, Users, X,
} from 'lucide';

window.Alpine = Alpine;
window.Swal = Swal;

Alpine.data('shell', () => ({
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
    profileOpen: false,
    dark: document.documentElement.classList.contains('dark'),
    toggleSidebar() {
        this.sidebarCollapsed = ! this.sidebarCollapsed;
        localStorage.setItem('sidebar-collapsed', String(this.sidebarCollapsed));
    },
    toggleTheme() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
}));

Alpine.data('questionAssignment', (config) => ({
    saving: false,
    search: '',
    async save(form) {
        this.saving = true;

        try {
            const response = await fetch(config.endpoint, {
                method: 'PUT',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.token },
                body: new FormData(form),
            });
            const data = await response.json();

            if (!response.ok) {
                const error = Object.values(data.errors ?? {})[0]?.[0] ?? 'Unable to save question assignment.';
                await Swal.fire({ icon: 'error', title: 'Assignment not saved', text: error });

                return;
            }

            await Swal.fire({ icon: 'success', title: data.message, timer: 1800, showConfirmButton: false });
        } catch {
            await Swal.fire({ icon: 'error', title: 'Network error', text: 'Please try saving the assignment again.' });
        } finally {
            this.saving = false;
        }
    },
}));

Alpine.data('examAttempt', (config) => ({
    remaining: Math.max(0, Math.floor((config.expiresAt - Date.now()) / 1000)),
    selected: {},
    status: {},
    versions: config.versions,
    chains: {},
    submitting: false,
    get formattedRemaining() {
        const minutes = String(Math.floor(this.remaining / 60)).padStart(2, '0');
        const seconds = String(this.remaining % 60).padStart(2, '0');

        return `${minutes}:${seconds}`;
    },
    init() {
        document.querySelectorAll('input[type="radio"]:checked').forEach((input) => {
            this.selected[input.name.replace('question_', '')] = input.value;
        });

        window.setInterval(() => {
            this.remaining = Math.max(0, Math.floor((config.expiresAt - Date.now()) / 1000));

            if (this.remaining === 0 && !this.submitting) {
                this.submit('timeout');
            }
        }, 1000);
    },
    saveAnswer(questionId, selectedOption) {
        this.selected[questionId] = selectedOption;
        this.status[questionId] = 'Saving…';
        this.chains[questionId] = (this.chains[questionId] ?? Promise.resolve()).then(() => this.persistAnswer(questionId, selectedOption));
    },
    async persistAnswer(questionId, selectedOption) {
        try {
            const response = await fetch(config.answerUrls[questionId], {
                method: 'PUT',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.token },
                body: JSON.stringify({ selected_option: selectedOption, version: this.versions[questionId] ?? 0 }),
            });
            const data = await response.json();

            if (response.status === 409 && data.version !== undefined) {
                this.versions[questionId] = data.version;
                this.selected[questionId] = data.selected_option;
                this.status[questionId] = 'A newer saved choice was restored.';

                return;
            }

            if (!response.ok) {
                this.status[questionId] = data.message ?? 'Unable to save. Please try again.';

                return;
            }

            this.versions[questionId] = data.version;
            this.status[questionId] = 'Saved';
        } catch {
            this.status[questionId] = 'Connection interrupted. Your next choice will retry.';
        }
    },
    async confirmSubmit() {
        const result = await Swal.fire({ icon: 'warning', title: 'Submit your examination?', text: 'Answers become read-only after submission.', showCancelButton: true, confirmButtonText: 'Submit now', confirmButtonColor: '#e11d48' });

        if (result.isConfirmed) {
            await this.submit('manual');
        }
    },
    async submit() {
        if (this.submitting) {
            return;
        }

        this.submitting = true;

        try {
            const response = await fetch(config.submitUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': config.token } });

            if (!response.ok) {
                throw new Error('Unable to submit');
            }

            window.location.assign('/student/my-exams');
        } catch {
            this.submitting = false;
            await Swal.fire({ icon: 'error', title: 'Submission not confirmed', text: 'Please use the Submit exam button again.' });
        }
    },
}));

Alpine.start();

const renderIcons = () => createIcons({
    icons: {
        Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, ChartNoAxesColumn,
        CalendarDays, ChevronDown, CircleCheck, CircleDotDashed, CircleHelp, ClipboardCheck,
        Clock3, Construction, FileClock, Filter, GraduationCap, LayoutDashboard, ListChecks,
        LogIn, LogOut, Menu, Moon, NotebookTabs, PanelLeftClose, Pencil, Play, Plus, Save, Search,
        Settings, Shield, ShieldCheck, SlidersHorizontal, Sparkles, Sun, Trash2, TrendingUp,
        Trophy, UserPlus, UserRound, Users, X,
    },
});
renderIcons();
document.addEventListener('alpine:initialized', renderIcons);

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-confirm]');

    if (!form || form.dataset.confirmed === 'true') {
        return;
    }

    event.preventDefault();
    Swal.fire({ icon: 'warning', title: 'Are you sure?', text: form.dataset.confirm, showCancelButton: true, confirmButtonText: 'Yes, continue', confirmButtonColor: '#e11d48' })
        .then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';
                form.requestSubmit();
            }
        });
});
