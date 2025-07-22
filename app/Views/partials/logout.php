<div style="display: flex; justify-content: center; align-items: center; height: auto; padding: 1rem;">
  <form action="<?= base_url('/logout') ?>" method="get">
    <button type="submit" style="
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
      padding: 6px 16px;
      background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    ">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: middle;">
        <path d="M7.5 1v7h1V1h-1z"/>
        <path d="M3 8.812a4.999 4.999 0 0 1 2.578-4.375l-.485-.874A6 6 0 1 0 11 3.616l-.501.865A5 5 0 1 1 3 8.812z"/>
      </svg>
      Cerrar sesión
    </button>
  </form>
</div>
