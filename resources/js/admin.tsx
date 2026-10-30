import { Component, useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/admin.css';
import { ArrowUpRight, BookOpen, CalendarDays, ChevronRight, FileText, Home, Image, LayoutDashboard, LogOut, MapPin, Menu, MessageCircle, Palette, PanelTop, Settings2, ShieldCheck, Smile, Stethoscope, Tags, UsersRound, X } from 'lucide-react';
import { useRemote } from './admin/api';
import { ErrorNotice, Loading, Toast } from './admin/components';
import { ContentManager } from './admin/ContentManager';
import { Dashboard } from './admin/Dashboard';
import { MediaLibrary } from './admin/MediaLibrary';
import { Requests } from './admin/Requests';
import { ActivityLog, HomepageSections, Settings } from './admin/Settings';
import { Appearance } from './admin/Appearance';
import { Users } from './admin/Users';
import type { Bootstrap } from './admin/types';

const contentIcons: Record<string, typeof Smile> = { services:Smile,professionals:Stethoscope,branches:MapPin,articles:BookOpen,pages:FileText,gallery:Image,testimonials:MessageCircle,faqs:MessageCircle,categories:Tags,tags:Tags,navigation:PanelTop };
function App() {
  const [view,setView]=useState(location.hash.slice(1)||'dashboard');const [mobile,setMobile]=useState(false);const [toast,setToast]=useState('');const [refresh,setRefresh]=useState(0);
  const remote=useRemote<Bootstrap>('/bootstrap',refresh);const boot=remote.data;
  useEffect(()=>{const change=()=>{setView(location.hash.slice(1)||'dashboard');setMobile(false);};const escape=(event:KeyboardEvent)=>{if(event.key==='Escape')setMobile(false);};window.addEventListener('hashchange',change);window.addEventListener('keydown',escape);return()=>{window.removeEventListener('hashchange',change);window.removeEventListener('keydown',escape);};},[]);
  const navigate=useCallback((next:string)=>{location.hash=next;},[]);const notify=useCallback((message:string)=>setToast(message),[]);const clearToast=useCallback(()=>setToast(''),[]);const reloadOptions=useCallback(()=>setRefresh(r=>r+1),[]);
  if(!boot)return remote.error?<div className="admin-loading"><ErrorNotice error={remote.error}/><a href="/login">Sign in again</a></div>:<Loading/>;
  const has=(ability:string)=>boot.abilities.includes(ability);
  function navItem(key:string,title:string,Icon:typeof Home) {return <button key={key} className={view===key?'active':''} onClick={()=>navigate(key)} aria-current={view===key?'page':undefined}><Icon size={18}/><span>{title}</span>{view===key&&<ChevronRight size={14}/>}</button>;}
  let content;
  if(view==='dashboard')content=<Dashboard boot={boot} navigate={navigate}/>;
  else if(boot.modules[view])content=<ContentManager key={view} name={view} module={boot.modules[view]} boot={boot} notify={notify} reloadOptions={reloadOptions}/>;
  else if(view==='appointments'&&has('appointments'))content=<Requests key={view} type="appointments" boot={boot} notify={notify}/>;
  else if(view==='messages'&&has('messages'))content=<Requests key={view} type="messages" boot={boot} notify={notify}/>;
  else if(view==='media'&&has('media'))content=<MediaLibrary notify={notify}/>;
  else if(view==='settings'&&has('settings'))content=<Settings boot={boot} notify={notify} reloadOptions={reloadOptions}/>;
  else if(view==='appearance'&&has('settings'))content=<Appearance userId={boot.user.id} notify={notify}/>;
  else if(view==='sections'&&has('content'))content=<HomepageSections notify={notify}/>;
  else if(view==='users'&&has('users'))content=<Users notify={notify}/>;
  else if(view==='activities'&&has('audit'))content=<ActivityLog/>;
  else content=<ErrorNotice error="This page is not available for your account."/>;
  async function logout(){try{const token=document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content??'';const response=await fetch('/logout',{method:'POST',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},credentials:'same-origin'});if(response.ok||response.redirected)location.href='/login';else notify('Please reload the page to sign out.');}catch{notify('Please check your connection and try again.');}}
  return <div className="admin-shell"><a className="skip-link" href="#admin-main">Skip to content</a>{mobile&&<button className="sidebar-scrim" aria-label="Close navigation" onClick={()=>setMobile(false)}/>}<aside className={`admin-sidebar ${mobile?'open':''}`}><a className="admin-brand" href="#dashboard"><span><Smile size={27} strokeWidth={1.2}/></span><div>{boot.clinic_name}<small>CLINIC WORKSPACE</small></div></a><button className="sidebar-close" aria-label="Close navigation" onClick={()=>setMobile(false)}><X size={20}/></button><nav aria-label="Clinic administration"><div className="sidebar-group"><span>WORKSPACE</span>{navItem('dashboard','Overview',LayoutDashboard)}{has('appointments')&&navItem('appointments','Appointment requests',CalendarDays)}{has('messages')&&navItem('messages','Contact messages',MessageCircle)}</div>{has('content')&&<div className="sidebar-group"><span>YOUR WEBSITE</span>{navItem('sections','Homepage sections',PanelTop)}{Object.entries(boot.modules).map(([key,module])=>navItem(key,module.label,contentIcons[key]??FileText))}{has('media')&&navItem('media','Media library',Image)}</div>}{(has('settings')||has('users'))&&<div className="sidebar-group"><span>ADMINISTRATION</span>{has('settings')&&navItem('appearance','Appearance studio',Palette)}{has('settings')&&navItem('settings','Branding & settings',Settings2)}{has('users')&&navItem('users','People & permissions',UsersRound)}{has('audit')&&navItem('activities','Activity log',ShieldCheck)}</div>}</nav><div className="sidebar-bottom"><div className="sidebar-help"><Settings2 size={19}/><span>A little care, behind the scenes.</span></div><div className="sidebar-user"><span className="user-initial">{boot.user.name.slice(0,1)}</span><div><strong>{boot.user.name}</strong><small>{boot.user.role.replaceAll('_',' ')}</small></div><button aria-label="Sign out" onClick={logout}><LogOut size={17}/></button></div></div></aside><div className="admin-main-wrap"><header className="admin-topbar"><div><button className="admin-menu" aria-label="Open navigation" onClick={()=>setMobile(true)}><Menu size={21}/></button><Home size={16}/><span>Workspace</span><ChevronRight size={13}/><strong>{view==='dashboard'?'Overview':boot.modules[view]?.label??view.replaceAll('_',' ')}</strong></div><a href="/" target="_blank" rel="noopener noreferrer">View website <ArrowUpRight size={16}/></a></header><main id="admin-main" className="admin-main">{content}</main><footer className="admin-footer"><span>{boot.clinic_name} · Website administration</span><span>Africa/Addis_Ababa</span></footer></div><Toast message={toast} clear={clearToast}/></div>;
}
class Boundary extends Component<{children:React.ReactNode},{error:boolean}> {
  state={error:false};static getDerivedStateFromError(){return {error:true};}
  render(){return this.state.error?<div className="admin-loading"><h1>Let’s reload your workspace.</h1><p>An unexpected problem occurred. Your saved content remains in the database.</p><button className="admin-button" onClick={()=>location.reload()}>Reload page</button></div>:this.props.children;}
}
createRoot(document.getElementById('admin-root')!).render(<Boundary><App/></Boundary>);
