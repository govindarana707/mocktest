import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import {
    Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, ChartNoAxesColumn,
    ChevronDown, CircleHelp, ClipboardCheck, Construction, createIcons, FileClock,
    GraduationCap, LayoutDashboard, LogIn, LogOut, Menu, Moon, NotebookTabs,
    PanelLeftClose, Save, Settings, Shield, ShieldCheck, Sparkles, Sun, TrendingUp,
    Trophy, UserPlus, UserRound, Users, X,
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

Alpine.start();

const renderIcons = () => createIcons({
    icons: {
        Activity, ArrowLeft, ArrowRight, BadgeCheck, BarChart3, BookOpen, ChartNoAxesColumn,
        ChevronDown, CircleHelp, ClipboardCheck, Construction, FileClock, GraduationCap,
        LayoutDashboard, LogIn, LogOut, Menu, Moon, NotebookTabs, PanelLeftClose, Save,
        Settings, Shield, ShieldCheck, Sparkles, Sun, TrendingUp, Trophy, UserPlus,
        UserRound, Users, X,
    },
});
renderIcons();
document.addEventListener('alpine:initialized', renderIcons);
