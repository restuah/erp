/**
 * Color Design Tokens (Quarks)
 * Business-friendly ERP color palette with verified light and dark mode tokens
 */
export const colors = {
    // Brand primary colors (Enterprise Corporate Blue)
    primary: {
        50: '#EFF6FF',
        100: '#DBEAFE',
        200: '#BFDBFE',
        300: '#93C5FD',
        400: '#60A5FA',
        500: '#3B82F6',
        600: '#2563EB', // Main Primary (Light)
        700: '#1D4ED8',
        800: '#1E40AF',
        900: '#1E3A8A',
        950: '#172554',
    },

    // Neutral / Slate scale for ERP clean surfaces
    slate: {
        50: '#F8FAFC',
        100: '#F1F5F9',
        200: '#E2E8F0',
        300: '#CBD5E1',
        400: '#94A3B8',
        500: '#64748B',
        600: '#475569',
        700: '#334155',
        800: '#1E293B',
        850: '#131B2E', // Deep Table Header / Subsurface
        900: '#0F172A', // Main Dark Card Surface
        950: '#020617', // Main Dark Canvas Background
    },

    // Dual-Mode Surface Tokens
    canvas: {
        light: '#F8FAFC',
        dark: '#020617',
    },
    surface: {
        light: '#FFFFFF',
        dark: '#0F172A',
    },
    subsurface: {
        light: '#F1F5F9',
        dark: '#131B2E',
    },
    border: {
        light: '#E2E8F0',
        dark: '#1E293B',
    },

    // Semantic Status Colors (Verified WCAG AA Contrast)
    success: {
        light: '#ECFDF5',
        border: '#A7F3D0',
        text: '#065F46',
        darkSurface: 'rgba(6, 78, 59, 0.4)',
        darkBorder: 'rgba(16, 185, 129, 0.3)',
        darkText: '#6EE7B7',
    },
    danger: {
        light: '#FFF1F2',
        border: '#FECDD3',
        text: '#9F1239',
        darkSurface: 'rgba(136, 19, 55, 0.4)',
        darkBorder: 'rgba(244, 63, 94, 0.3)',
        darkText: '#FDA4AF',
    },
    warning: {
        light: '#FFFBEB',
        border: '#FDE68A',
        text: '#92400E',
        darkSurface: 'rgba(120, 53, 15, 0.4)',
        darkBorder: 'rgba(245, 158, 11, 0.3)',
        darkText: '#FCD34D',
    },
    info: {
        light: '#EFF6FF',
        border: '#BFDBFE',
        text: '#1E40AF',
        darkSurface: 'rgba(30, 58, 138, 0.4)',
        darkBorder: 'rgba(59, 130, 246, 0.3)',
        darkText: '#93C5FD',
    },
};
