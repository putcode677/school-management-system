import { useEffect, useMemo, useState } from "react";
import {
  ArrowRight,
  BookOpen,
  CalendarCheck,
  GraduationCap,
  LayoutDashboard,
  LogOut,
  Menu,
  Plus,
  Search,
  School,
  Settings,
  ShieldCheck,
  Trash2,
  Users,
  X,
} from "lucide-react";

const api = async (action, options = {}) => {
  const response = await fetch(`/api/index.php?action=${action}`, {
    credentials: "include",
    headers: { "Content-Type": "application/json", ...options.headers },
    ...options,
  });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.error || "Request failed.");
  return payload;
};

const roleLabels = {
  admin: "Administrator",
  teacher: "Teacher",
  student: "Student",
  parent: "Parent",
};

const initials = (name = "") =>
  name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join("") || "?";

function Login({ onLogin }) {
  const [form, setForm] = useState({ credential: "", password: "" });
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async (event) => {
    event.preventDefault();
    setError("");
    setLoading(true);
    try {
      const result = await api("login", {
        method: "POST",
        body: JSON.stringify(form),
      });
      onLogin(result.user);
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="auth-page">
      <section className="auth-card">
        <div className="brand-mark"><School size={28} /></div>
        <p className="eyebrow">SCHOOL MANAGEMENT SYSTEM</p>
        <h1>Welcome back</h1>
        <p className="muted">Sign in to manage your school workspace.</p>
        {error && <div className="alert error">{error}</div>}
        <form onSubmit={submit} className="stack">
          <label>
            Email or account number
            <input
              autoComplete="username"
              value={form.credential}
              onChange={(event) => setForm({ ...form, credential: event.target.value })}
              required
            />
          </label>
          <label>
            Password
            <input
              type="password"
              autoComplete="current-password"
              value={form.password}
              onChange={(event) => setForm({ ...form, password: event.target.value })}
              required
            />
          </label>
          <button className="primary-button" disabled={loading}>
            {loading ? "Signing in..." : "Sign in"} <ArrowRight size={17} />
          </button>
        </form>
        <p className="auth-footer">Secure role-based access for your school community.</p>
      </section>
    </main>
  );
}

function Sidebar({ user, active, onNavigate, onLogout, open, onClose }) {
  const adminItems = [
    ["dashboard", "Overview", LayoutDashboard],
    ["students", "Students", GraduationCap],
    ["teachers", "Teachers", Users],
    ["parents", "Parents", Users],
    ["classes", "Classes", School],
    ["subjects", "Subjects", BookOpen],
  ];
  const roleItems = user.role === "admin"
    ? adminItems
    : [
        ["dashboard", "Overview", LayoutDashboard],
        ["attendance", "Attendance", CalendarCheck],
        ["subjects", "Subjects", BookOpen],
        ["profile", "My profile", Settings],
      ];

  return (
    <>
      {open && <button className="sidebar-backdrop" onClick={onClose} aria-label="Close menu" />}
      <aside className={`sidebar ${open ? "open" : ""}`}>
        <div className="sidebar-brand">
          <div className="brand-mark small"><School size={21} /></div>
          <div><strong>School OS</strong><span>Management system</span></div>
          <button className="icon-button mobile-only" onClick={onClose} aria-label="Close menu"><X size={19} /></button>
        </div>
        <p className="nav-label">Workspace</p>
        <nav>
          {roleItems.map(([key, label, Icon]) => (
            <button key={key} className={`nav-item ${active === key ? "active" : ""}`} onClick={() => onNavigate(key)}>
              <Icon size={18} /><span>{label}</span>
            </button>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <div className="sidebar-user"><span className="avatar">{initials(user.full_name)}</span><div><strong>{user.full_name}</strong><span>{roleLabels[user.role]}</span></div></div>
          <button className="nav-item logout-button" onClick={onLogout}><LogOut size={18} /><span>Sign out</span></button>
        </div>
      </aside>
    </>
  );
}

function StatCard({ label, value, icon: Icon, tone }) {
  return <article className={`stat-card ${tone}`}><div className="stat-icon"><Icon size={20} /></div><div><strong>{value ?? "—"}</strong><span>{label}</span></div></article>;
}

function DataTable({ title, rows, columns, empty = "No records found.", onDelete }) {
  const [query, setQuery] = useState("");
  const filteredRows = rows.filter((row) => Object.values(row).some((value) => String(value ?? "").toLowerCase().includes(query.toLowerCase())));
  return <section className="panel"><div className="panel-heading table-toolbar"><div><h2>{title}</h2><p className="muted">Live data from your school database.</p></div><div className="search-box"><Search size={16} /><input placeholder="Search records" value={query} onChange={(event) => setQuery(event.target.value)} /></div></div>
    {filteredRows.length ? <div className="table-wrap"><table><thead><tr>{columns.map(([label]) => <th key={label}>{label}</th>)}{onDelete && <th>Actions</th>}</tr></thead><tbody>{filteredRows.map((row, index) => <tr key={row.id ?? index}>{columns.map(([, field, render]) => <td key={field}>{render ? render(row) : row[field] || "—"}</td>)}{onDelete && <td><button className="table-action danger" onClick={() => onDelete(row)} aria-label={`Delete ${row.full_name}`}><Trash2 size={15} /></button></td>}</tr>)}</tbody></table></div> : <div className="empty-state">{query ? "No records match your search." : empty}</div>}
  </section>;
}

function StudentForm({ classes, onClose, onCreated }) {
  const [form, setForm] = useState({ full_name: "", email: "", password: "", student_number: "", class_id: "", date_of_birth: "", gender: "", phone: "", address: "", admission_date: "" });
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);
  const update = (field) => (event) => setForm({ ...form, [field]: event.target.value });
  const submit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    try {
      await api("create-student", { method: "POST", body: JSON.stringify(form) });
      onCreated();
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setSaving(false);
    }
  };
  return <div className="modal-backdrop"><section className="modal-card"><div className="modal-heading"><div><p className="eyebrow">NEW RECORD</p><h2>Add student</h2></div><button className="icon-button" onClick={onClose} aria-label="Close dialog"><X size={20} /></button></div>{error && <div className="alert error">{error}</div>}<form className="form-grid" onSubmit={submit}>
    <label>Full name<input value={form.full_name} onChange={update("full_name")} required /></label><label>Email<input type="email" value={form.email} onChange={update("email")} required /></label>
    <label>Student number<input value={form.student_number} onChange={update("student_number")} required /></label><label>Temporary password<input type="password" value={form.password} onChange={update("password")} minLength={12} required /></label>
    <label>Class<select value={form.class_id} onChange={update("class_id")}><option value="">Unassigned</option>{classes.map((item) => <option key={item.id} value={item.id}>{item.class_name} ({item.class_code})</option>)}</select></label>
    <label>Gender<select value={form.gender} onChange={update("gender")}><option value="">Not specified</option><option>Male</option><option>Female</option><option>Other</option></select></label>
    <label>Date of birth<input type="date" value={form.date_of_birth} onChange={update("date_of_birth")} /></label><label>Admission date<input type="date" value={form.admission_date} onChange={update("admission_date")} /></label>
    <label>Phone<input value={form.phone} onChange={update("phone")} /></label><label>Address<input value={form.address} onChange={update("address")} /></label>
    <div className="modal-actions"><button type="button" className="secondary-button" onClick={onClose}>Cancel</button><button className="primary-button" disabled={saving}>{saving ? "Creating..." : "Create student"}</button></div>
  </form></section></div>;
}

function Dashboard({ user, data, onNavigate }) {
  const stats = data?.stats || {};
  const admin = user.role === "admin";
  return <div className="page-content">
    <div className="page-heading"><div><p className="eyebrow">OVERVIEW</p><h1>Good morning, {user.full_name.split(" ")[0]}</h1><p className="muted">Here’s what is happening across your school today.</p></div></div>
    {admin ? <div className="stats-grid">
      <StatCard label="Students" value={stats.students} icon={GraduationCap} tone="blue" />
      <StatCard label="Teachers" value={stats.teachers} icon={Users} tone="violet" />
      <StatCard label="Parents" value={stats.parents} icon={Users} tone="green" />
      <StatCard label="Classes" value={stats.classes} icon={School} tone="orange" />
    </div> : <div className="stats-grid">
      <StatCard label="Role" value={roleLabels[user.role]} icon={ShieldCheck} tone="blue" />
      <StatCard label="Subjects" value={stats.subjects} icon={BookOpen} tone="violet" />
      <StatCard label="Attendance records" value={stats.attendance} icon={CalendarCheck} tone="green" />
    </div>}
    <section className="welcome-panel"><div><p className="eyebrow">YOUR WORKSPACE</p><h2>{admin ? "Run your school from one place." : "Everything you need, in one place."}</h2><p className="muted">Use the navigation to access live records and role-specific tools.</p></div><div className="welcome-art"><School size={58} /></div></section>
    {admin && <div className="quick-links">{[["students", "Manage students", GraduationCap], ["teachers", "Manage teachers", Users], ["classes", "Manage classes", School], ["subjects", "Manage subjects", BookOpen]].map(([key, label, Icon]) => <button key={key} onClick={() => onNavigate(key)}><Icon size={21} /><span>{label}</span><ArrowRight size={17} /></button>)}</div>}
  </div>;
}

function App() {
  const [user, setUser] = useState(null);
  const [active, setActive] = useState("dashboard");
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [error, setError] = useState("");
  const [showStudentForm, setShowStudentForm] = useState(false);

  useEffect(() => {
    api("me").then((result) => setUser(result.user)).catch(() => {}).finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    if (!user) return;
    setLoading(true);
    const request = active === "dashboard"
      ? api("dashboard")
      : active === "students"
        ? Promise.all([api("resource&name=students"), api("resource&name=classes")]).then(([students, classes]) => ({ rows: students.rows, classes: classes.rows }))
        : api(`resource&name=${active}`);
    request.then(setData)
      .catch((requestError) => setError(requestError.message))
      .finally(() => setLoading(false));
  }, [user, active]);

  const navigate = (page) => { setActive(page); setSidebarOpen(false); setError(""); };
  const logout = async () => { await api("logout", { method: "POST" }).catch(() => {}); setUser(null); };
  const refresh = () => {
    setLoading(true);
    Promise.all([api("resource&name=students"), api("resource&name=classes")])
      .then(([students, classes]) => setData({ rows: students.rows, classes: classes.rows }))
      .catch((requestError) => setError(requestError.message))
      .finally(() => setLoading(false));
  };
  const deleteStudent = async (student) => {
    if (!window.confirm(`Delete ${student.full_name}? This also removes the student account.`)) return;
    try {
      await api("delete-student", { method: "POST", body: JSON.stringify({ id: student.id }) });
      refresh();
    } catch (requestError) {
      setError(requestError.message);
    }
  };
  const rows = useMemo(() => data?.rows || [], [data]);

  if (loading && !user) return <div className="loading-screen">Loading your workspace...</div>;
  if (!user) return <Login onLogin={setUser} />;
  const columns = {
    students: [["Name", "full_name"], ["Student number", "student_number"], ["Class", "class_name"], ["Email", "email"]],
    teachers: [["Name", "full_name"], ["Employee number", "employee_number"], ["Specialization", "specialization"], ["Email", "email"]],
    parents: [["Name", "full_name"], ["Parent number", "parent_number"], ["Phone", "phone"], ["Email", "email"]],
    classes: [["Class", "class_name"], ["Code", "class_code"], ["Description", "description"]],
    subjects: [["Subject", "subject_name"], ["Code", "subject_code"], ["Description", "description"]],
  };

  return <div className="app-shell">
    <Sidebar user={user} active={active} onNavigate={navigate} onLogout={logout} open={sidebarOpen} onClose={() => setSidebarOpen(false)} />
    <main className="main-content"><header className="topbar"><button className="icon-button mobile-menu" onClick={() => setSidebarOpen(true)} aria-label="Open menu"><Menu size={21} /></button><div className="breadcrumb">School OS <span>/</span> {active === "dashboard" ? "Overview" : active}</div><div className="topbar-user"><span className="avatar">{initials(user.full_name)}</span><span>{user.full_name}</span></div></header>
      {error && <div className="alert error page-alert">{error}</div>}
      {loading ? <div className="loading-state">Loading...</div> : active === "dashboard" ? <Dashboard user={user} data={data} onNavigate={navigate} /> : columns[active] ? <div className="page-content"><div className="page-heading"><div><p className="eyebrow">DIRECTORY</p><h1>{active[0].toUpperCase() + active.slice(1)}</h1><p className="muted">Browse and manage your school records.</p></div>{active === "students" && <button className="primary-button compact-button" onClick={() => setShowStudentForm(true)}><Plus size={16} /> Add student</button>}</div><DataTable title={`All ${active}`} rows={rows} columns={columns[active]} onDelete={active === "students" ? deleteStudent : undefined} /></div> : <div className="page-content"><div className="panel empty-state"><CalendarCheck size={30} /><h2>{active[0].toUpperCase() + active.slice(1)}</h2><p className="muted">This workspace is ready for the next workflow implementation.</p></div></div>}
    </main>
    {showStudentForm && <StudentForm classes={data?.classes || []} onClose={() => setShowStudentForm(false)} onCreated={() => { setShowStudentForm(false); refresh(); }} />}
  </div>;
}

export default App;
