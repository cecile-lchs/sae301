
let currentDate = new Date();
const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];

document.addEventListener('DOMContentLoaded', function () {
    renderCalendar();
});

function changeMonth(step) {
    currentDate.setMonth(currentDate.getMonth() + step);
    renderCalendar();
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Update Titles
    const title = `${monthNames[month]} ${year}`;
    const mainTitle = document.getElementById('main-calendar-title');
    const miniTitle = document.getElementById('mini-calendar-title');

    if (mainTitle) mainTitle.innerText = title;
    if (miniTitle) miniTitle.innerText = title;

    // Calendar Data construction
    const firstDayIndex = new Date(year, month, 1).getDay(); // 0 is Sunday
    // Adjust for Monday first: 0->6, 1->0, etc.
    const startingDay = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Render Mini Calendar
    renderMiniCalendar(startingDay, daysInMonth);

    // Render Main Month View
    renderMainMonthGrid(startingDay, daysInMonth);
}

function renderMiniCalendar(startDay, daysCount) {
    const tbody = document.getElementById('mini-calendar-body');
    if (!tbody) return;

    tbody.innerHTML = '';
    let date = 1;
    let row = document.createElement('tr');

    // Empty cells before start
    for (let i = 0; i < startDay; i++) {
        const cell = document.createElement('td');
        cell.classList.add('text-muted');
        row.appendChild(cell);
    }

    for (let i = startDay; i < 7; i++) {
        if (date > daysCount) break;
        const cell = document.createElement('td');
        cell.innerText = date;
        if (isToday(date)) {
            cell.classList.add('bg-primary', 'text-white', 'rounded-circle');
        }
        row.appendChild(cell);
        date++;
    }
    tbody.appendChild(row);

    while (date <= daysCount) {
        row = document.createElement('tr');
        for (let i = 0; i < 7; i++) {
            if (date > daysCount) {
                const cell = document.createElement('td');
                row.appendChild(cell);
            } else {
                const cell = document.createElement('td');
                cell.innerText = date;
                if (isToday(date)) {
                    cell.classList.add('bg-primary', 'text-white', 'rounded-circle');
                }
                row.appendChild(cell);
                date++;
            }
        }
        tbody.appendChild(row);
    }
}

function renderMainMonthGrid(startDay, daysCount) {
    const grid = document.getElementById('main-month-grid');
    if (!grid) return;

    grid.innerHTML = '';
    let date = 1;

    // Fill blanks
    for (let i = 0; i < startDay; i++) {
        const div = document.createElement('div');
        div.className = 'col border p-1 bg-light';
        grid.appendChild(div);
    }

    // Fill days
    // We need 42 cells (6 rows * 7 cols) to keep grid stable, or just auto-fill
    // The previous design used row-cols-7

    while (date <= daysCount) {
        const div = document.createElement('div');
        div.className = 'col border p-1 fw-bold position-relative';
        div.style.minHeight = '100px';

        div.innerHTML = `<span>${date}</span>`;

        // Example mock event on the 14th
        if (date === 14) {
            div.classList.add('bg-primary-subtle');
            div.classList.add('cursor-pointer');
            div.innerHTML += `
                <div class="badge bg-primary d-block text-truncate mt-1 event-item"
                     onclick="event.stopPropagation(); showEventDetailsFromData(this)"
                     data-title="Tonte standard"
                     data-client="Mme. Martin"
                     data-time="09:00 - 11:00"
                     data-address="8 Avenue des Roses, Lyon"
                     data-desc="Tonte de la pelouse avant et arrière.">
                    Mme Martin
                </div>
             `;
        }

        if (isToday(date)) {
            div.style.backgroundColor = 'rgba(13, 110, 253, 0.05)';
            div.querySelector('span').classList.add('badge', 'bg-primary');
        }

        grid.appendChild(div);
        date++;
    }

    // Fill remaining cells to look nice (optional but good for grid)
    const totalCells = grid.children.length;
    const remaining = 7 - (totalCells % 7);
    if (remaining < 7) {
        for (let i = 0; i < remaining; i++) {
            const div = document.createElement('div');
            div.className = 'col border p-1 bg-light';
            grid.appendChild(div);
        }
    }
}

function isToday(date) {
    const today = new Date();
    return date === today.getDate() &&
        currentDate.getMonth() === today.getMonth() &&
        currentDate.getFullYear() === today.getFullYear();
}

// Function helper to reuse the showEventDetails logic if needed,
// though the original HTML one works if data attributes are present.
function showEventDetailsFromData(element) {
    // This wrapper is needed because renderMainMonthGrid creates dynamic elements
    // that might need to trigger the global showEventDetails function defined in HTML
    if (window.showEventDetails) {
        window.showEventDetails(element);
    }
}
