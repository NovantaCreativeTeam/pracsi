export const REGION_COLORS = [
    'rgba(153, 102, 255, 0.2)',  // Purple (moved up)
    'rgba(54, 162, 235, 0.2)',   // Blue
    'rgba(255, 206, 86, 0.2)',   // Yellow
    'rgba(75, 192, 192, 0.2)',   // Green
    'rgba(255, 159, 64, 0.2)',   // Orange
    'rgba(199, 199, 199, 0.2)',  // Grey
    'rgba(83, 102, 255, 0.2)',   // Indigo
    'rgba(40, 167, 69, 0.2)',    // Success Green
    'rgba(23, 162, 184, 0.2)',   // Cyan
    'rgba(107, 114, 128, 0.2)'   // Slate/Cool Grey instead of Red
];

export const getParticipantColor = (role) => {
    if (!role) return '#64748b'; // Slate 500

    const r = role.toLowerCase();

    if (r.includes('waiter') || r.includes('cameriere')) {
        return 'rgba(54, 162, 235, 1)'; // Blue
    }
    if (r.includes('customer') || r.includes('cliente') || r.includes('speaking customer')) {
        return 'rgba(153, 102, 255, 1)'; // Purple (was Red)
    }
    if (r.includes('researcher') || r.includes('ricercatore')) {
        return 'rgba(75, 192, 192, 1)'; // Green
    }
    if (r.includes('manager') || r.includes('gestore')) {
        return 'rgba(255, 206, 86, 1)'; // Yellow
    }

    return '#64748b'; // Default
};

export const getParticipantLightColor = (role) => {
    if (!role) return '#f8fafc'; // Slate 50

    const r = role.toLowerCase();

    if (r.includes('waiter') || r.includes('cameriere')) {
        return 'rgba(54, 162, 235, 0.1)'; // Blue Light
    }
    if (r.includes('customer') || r.includes('cliente') || r.includes('speaking customer')) {
        return 'rgba(153, 102, 255, 0.1)'; // Purple Light (was Red Light)
    }
    if (r.includes('researcher') || r.includes('ricercatore')) {
        return 'rgba(75, 192, 192, 0.1)'; // Green Light
    }
    if (r.includes('manager') || r.includes('gestore')) {
        return 'rgba(255, 206, 86, 0.1)'; // Yellow Light
    }

    return '#f8fafc'; // Default
};
