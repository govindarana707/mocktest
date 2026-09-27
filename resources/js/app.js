import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import {
    Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, CalendarDays,
    ChartNoAxesColumn, ChevronDown, CircleCheck, CircleDotDashed, CircleHelp,
    ClipboardCheck, Clock3, Construction, createIcons, FileClock, Filter, GraduationCap,
    LayoutDashboard, ListChecks, LogIn, LogOut, Menu, Moon, NotebookTabs, PanelLeftClose,
    Pencil, Plus, Save, Search, Settings, Shield, ShieldCheck, SlidersHorizontal, Sparkles,
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

Alpine.start();

const renderIcons = () => createIcons({
    icons: {
        Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, ChartNoAxesColumn,
        CalendarDays, ChevronDown, CircleCheck, CircleDotDashed, CircleHelp, ClipboardCheck,
        Clock3, Construction, FileClock, Filter, GraduationCap, LayoutDashboard, ListChecks,
        LogIn, LogOut, Menu, Moon, NotebookTabs, PanelLeftClose, Pencil, Plus, Save, Search,
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
