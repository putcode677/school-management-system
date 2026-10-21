CREATE TABLE attendance_sessions (
    id SERIAL PRIMARY KEY,
    class_id INTEGER NOT NULL REFERENCES classes(id) ON DELETE RESTRICT,
    attendance_date DATE NOT NULL,
    created_by INTEGER NOT NULL REFERENCES teachers(id) ON DELETE RESTRICT,
    updated_by INTEGER NOT NULL REFERENCES teachers(id) ON DELETE RESTRICT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (class_id, attendance_date)
);

CREATE TABLE attendance_records (
    id SERIAL PRIMARY KEY,
    session_id INTEGER NOT NULL REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE RESTRICT,
    status VARCHAR(20) NOT NULL CHECK (status IN ('present', 'absent', 'late', 'excused')),
    recorded_by INTEGER NOT NULL REFERENCES teachers(id) ON DELETE RESTRICT,
    updated_by INTEGER REFERENCES teachers(id) ON DELETE RESTRICT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (session_id, student_id)
);

CREATE TABLE attendance_changes (
    id SERIAL PRIMARY KEY,
    attendance_record_id INTEGER NOT NULL REFERENCES attendance_records(id) ON DELETE CASCADE,
    previous_status VARCHAR(20) NOT NULL CHECK (previous_status IN ('present', 'absent', 'late', 'excused')),
    new_status VARCHAR(20) NOT NULL CHECK (new_status IN ('present', 'absent', 'late', 'excused')),
    changed_by INTEGER NOT NULL REFERENCES teachers(id) ON DELETE RESTRICT,
    changed_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX attendance_sessions_date_idx ON attendance_sessions (attendance_date DESC);
CREATE INDEX attendance_records_student_idx ON attendance_records (student_id, session_id);
CREATE INDEX attendance_changes_record_idx ON attendance_changes (attendance_record_id, changed_at DESC);
