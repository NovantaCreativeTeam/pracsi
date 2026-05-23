import WaveSurfer from 'wavesurfer.js';

export default class TranscriptPlugin extends WaveSurfer.BasePlugin {
    /**
     * Transcript plugin definition factory
     *
     * @param  {Object} params parameters use to initialise the plugin
     * @return {TranscriptPlugin} an instance of the plugin
     */
    static create(params) {
        return new TranscriptPlugin(params);
    }

    constructor(params) {
        super(params);

        this.params = params;
        this.container =
            'string' == typeof params.container
                ? document.querySelector(params.container)
                : params.container;

        if (!this.container) {
            throw Error('No container for Transcript');
        }

        this.moves = params.moves || [];
    }

    onInit() {
        this.bindClick();
        this.render();
    }

    destroy() {
        if (this.container && this._onClick) {
            this.container.removeEventListener('click', this._onClick);
        }
        if (this.container && this.table) {
            this.container.removeChild(this.table);
        }
        super.destroy();
    }

    setMoves(moves) {
        this.moves = moves;
        this.render();
    }

    render() {
        if (!this.moves || this.moves.length === 0) {
            this.container.innerHTML = '<div class="p-4 text-gray-500 italic">Nessuna trascrizione disponibile</div>';
            return;
        }

        // Identify unique participants
        const participants = [];
        const participantMap = {};
        this.moves.forEach(move => {
            if (move.participant && !participantMap[move.participant.id]) {
                participantMap[move.participant.id] = move.participant;
                participants.push(move.participant);
            }
        });

        // Sort participants by code if available
        participants.sort((a, b) => (a.code || '').localeCompare(b.code || ''));

        // table
        const table = (this.table = document.createElement('table'));
        table.className = 'wavesurfer-annotations';

        // head
        const thead = document.createElement('thead');
        const headRow = document.createElement('tr');
        thead.appendChild(headRow);
        table.appendChild(thead);

        const columns = [
            'Time',
            ...participants.map(p => p.code),
            'Pausa',
            'Task',
            'Microtask',
            'Interactional Segment',
            'Sequence',
            'Transaction',
            'Move Level 1',
            'Move Level 2',
            'Move Level 3'
        ];

        columns.forEach(col => {
            const th = document.createElement('th');
            th.textContent = col;
            headRow.appendChild(th);
        });

        // body
        const tbody = (this.tbody = document.createElement('tbody'));
        table.appendChild(tbody);

        this.moves.forEach(move => {
            const row = document.createElement('tr');
            row.id = 'wavesurfer-move-' + move.id;
            row.className = 'wavesurfer-move-row';
            row.dataset.start = move.begin / 1000;
            row.dataset.end = move.end / 1000;
            tbody.appendChild(row);

            // Time
            const tdTime = document.createElement('td');
            tdTime.className = 'wavesurfer-time';
            tdTime.textContent = (move.begin / 1000).toFixed(2) + '–' + (move.end / 1000).toFixed(2);
            row.appendChild(tdTime);

            // Participants columns
            participants.forEach(p => {
                const td = document.createElement('td');
                if (move.participant && move.participant.id === p.id) {
                    td.textContent = move.annotation || '';
                    td.className = 'wavesurfer-annotation-cell';
                } else {
                    td.textContent = '';
                }
                row.appendChild(td);
            });

            // Pause column
            const tdPause = document.createElement('td');
            if (!move.participant) {
                const duration = (move.end - move.begin) / 1000;
                if (duration >= 0.2) {
                    tdPause.textContent = `(${duration.toFixed(2)})`;
                    tdPause.className = 'wavesurfer-pause-cell font-italic text-gray-400';
                } else {
                    tdPause.textContent = '';
                }
            } else {
                tdPause.textContent = '';
            }
            row.appendChild(tdPause);

            // Task
            const tdTask = document.createElement('td');
            tdTask.textContent = move.micro_task?.task?.type?.name || '-';
            row.appendChild(tdTask);

            // Microtask
            const tdMicrotask = document.createElement('td');
            tdMicrotask.textContent = move.micro_task?.type?.name || '-';
            row.appendChild(tdMicrotask);

            // Interactional Segment
            const tdIS = document.createElement('td');
            tdIS.textContent = move.sequence?.interactional_segment_id ? `IS ${move.sequence.interactional_segment_id}` : '-';
            row.appendChild(tdIS);

            // Sequence
            const tdSeq = document.createElement('td');
            tdSeq.textContent = move.sequence?.type?.name || '-';
            row.appendChild(tdSeq);

            // Transaction
            const tdTrans = document.createElement('td');
            tdTrans.textContent = move.transaction?.name || '-';
            row.appendChild(tdTrans);

            // Move Levels
            const tdML1 = document.createElement('td');
            tdML1.textContent = move.move_level1?.name || move.moveLevel1?.name || '-';
            row.appendChild(tdML1);

            const tdML2 = document.createElement('td');
            tdML2.textContent = move.move_level2?.name || move.moveLevel2?.name || '-';
            row.appendChild(tdML2);

            const tdML3 = document.createElement('td');
            tdML3.textContent = move.move_level3?.name || move.moveLevel3?.name || '-';
            row.appendChild(tdML3);
        });

        this.container.innerHTML = '';
        this.container.appendChild(table);
    }

    bindClick() {
        this._onClick = e => {
            const row = e.target.closest('.wavesurfer-move-row');
            if (row && row.dataset.start) {
                const start = parseFloat(row.dataset.start);
                const end = parseFloat(row.dataset.end);
                this.emit('select', start, end);
            }
        };
        this.container.addEventListener('click', this._onClick);
    }

    highlight(time) {
        if (!this.table) return;

        const activeClass = 'wavesurfer-active-row';
        const rows = this.table.querySelectorAll('.wavesurfer-move-row');

        let firstActive = null;

        rows.forEach(row => {
            const start = parseFloat(row.dataset.start);
            const end = parseFloat(row.dataset.end);

            if (time >= start && time <= end) {
                row.classList.add(activeClass);
                if (!firstActive) firstActive = row;
            } else {
                row.classList.remove(activeClass);
            }
        });

        if (firstActive && this.params.scrollToActive) {
            firstActive.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }
}
