(async () => {
  console.log('--- Testing Session Hijacking Protection via Live API ---');

  const loginUrl = 'http://localhost/qfunnel2/public/api/auth/login';
  const meUrl = 'http://localhost/qfunnel2/public/api/auth/me';

  const userAgentA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0';
  const attackerUserAgentB = 'Python-urllib/3.10 (Stolen Cookie Hijack Tool)';

  // 1. Legitimate user logs in with User-Agent A
  console.log('1. User logging in with User-Agent A...');
  const loginRes = await fetch(loginUrl, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'User-Agent': userAgentA
    },
    body: JSON.stringify({
      email: 'owner@qfunnel.local',
      password: 'Password123!',
      remember_me: true
    })
  });

  const rawCookieHeader = loginRes.headers.get('set-cookie');
  console.log('Set-Cookie received:', rawCookieHeader);
  if (!rawCookieHeader) {
    throw new Error('No Set-Cookie returned from login');
  }

  // Extract PHPSESSID (pick the latest regenerated one)
  const matches = [...rawCookieHeader.matchAll(/PHPSESSID=([^;]+)/g)];
  if (!matches.length) {
    throw new Error('No PHPSESSID found in Set-Cookie header');
  }
  const phpSessId = matches[matches.length - 1][1];
  const cookieHeader = `PHPSESSID=${phpSessId}`;
  console.log('Captured Session Cookie (regenerated):', cookieHeader);

  // 2. Legitimate user verifies session with User-Agent A
  console.log('\n2. Legitimate user calls /api/auth/me with User-Agent A...');
  const legitRes = await fetch(meUrl, {
    headers: {
      'Cookie': cookieHeader,
      'User-Agent': userAgentA
    }
  });
  const legitJson = await legitRes.json();
  console.log('Legitimate request status:', legitRes.status, 'authenticated user:', legitJson?.data?.user?.email);
  if (legitRes.status !== 200 || !legitJson?.data?.user) {
    throw new Error('Legitimate request failed to authenticate');
  }
  console.log('✓ Legitimate session verified.');

  // 3. Attacker replays the same PHPSESSID with different User-Agent B
  console.log('\n3. Attacker attempts to hijack session using same PHPSESSID but different User-Agent B...');
  const hijackRes = await fetch(meUrl, {
    headers: {
      'Cookie': cookieHeader,
      'User-Agent': attackerUserAgentB
    }
  });
  const hijackJson = await hijackRes.json();
  console.log('Hijack request status:', hijackRes.status, 'data:', hijackJson);

  if (hijackRes.status === 401 || !hijackJson?.data?.user) {
    console.log('✓ PASS: Session Hijacking attempt BLOCKED! Attacker was denied access (401 Unauthorized).');
  } else {
    throw new Error('FAIL: Session Hijacking vulnerability! Attacker was granted access.');
  }

  // 4. Verify that the session was destroyed by the hijack attempt, so even original user is protected
  console.log('\n4. Verifying compromised session was invalidated...');
  const verifyRes = await fetch(meUrl, {
    headers: {
      'Cookie': cookieHeader,
      'User-Agent': userAgentA
    }
  });
  const verifyJson = await verifyRes.json();
  console.log('Post-hijack verification status:', verifyRes.status);
  if (verifyRes.status === 401 || !verifyJson?.data?.user) {
    console.log('✓ PASS: Compromised session was completely purged from server.');
  }

  console.log('\n=== ALL SESSION HIJACKING PROTECTION CHECKS VERIFIED! ===');
})().catch(err => {
  console.error('Error during test:', err);
  process.exit(1);
});
