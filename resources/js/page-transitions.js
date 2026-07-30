/**
 * Page Transition Orchestrator
 * Handles smooth exit animations for Inertia.js
 */

export const performExitTransition = (elementId, callback) => {
    const element = document.getElementById(elementId);
    if (!element) {
        callback();
        return;
    }

    // Add exit class
    element.classList.add('page-exit-active');

    // Wait for animation to finish (match CSS duration)
    const animationDuration = 600; 
    
    setTimeout(() => {
        callback();
    }, animationDuration);
};
