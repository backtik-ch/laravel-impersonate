@if(app('impersonate')->isImpersonating())
  <div
    style="background-color: #4f46e5; color: #ffffff; padding: 0.5rem 1rem; display: flex; justify-content: center; align-items: center; gap: 0.75rem; font-weight: 500; font-size: 0.875rem; border-bottom: 1px solid #4338ca;">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
      stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
      <circle cx="12" cy="12" r="3"></circle>
    </svg>
    <span>
      {{ __('Impersonating') }} — <strong>{{ Auth::user()->name ?? Auth::user()->email }}</strong>
    </span>
    <form method="POST" action="{{ route('impersonate.leave') }}" style="display: inline;">
      @csrf
      <button type="submit"
        style="background-color: #ffffff; color: #4f46e5; border: none; border-radius: 0.375rem; padding: 0.25rem 0.75rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
        {{ __('Leave impersonation') }}
      </button>
    </form>
  </div>
@endif