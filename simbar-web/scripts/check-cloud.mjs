async function checkCloudinary() {
  const cloudName = process.env.CLOUDINARY_CLOUD_NAME?.trim();
  const apiKey = process.env.CLOUDINARY_API_KEY?.trim();
  const apiSecret = process.env.CLOUDINARY_API_SECRET?.trim();

  if (!cloudName || !/^[a-z0-9_-]+$/.test(cloudName)) {
    throw new Error('Set CLOUDINARY_CLOUD_NAME to the SIMBAR cloud name.');
  }
  if (!apiKey || !apiSecret) {
    throw new Error('Set CLOUDINARY_API_KEY and CLOUDINARY_API_SECRET privately before testing.');
  }

  const credentials = Buffer.from(`${apiKey}:${apiSecret}`, 'utf8').toString('base64');
  const response = await fetch(`https://api.cloudinary.com/v1_1/${cloudName}/ping`, {
    headers: { Authorization: `Basic ${credentials}` },
    redirect: 'error',
    signal: AbortSignal.timeout(10_000),
  }).catch(() => {
    throw new Error('Could not reach the Cloudinary API.');
  });

  if (!response.ok) {
    throw new Error(`Cloudinary ping failed (HTTP ${response.status}).`);
  }
  const result = await response.json().catch(() => null);
  if (result?.status !== 'ok') {
    throw new Error('Cloudinary did not confirm the connection.');
  }
  console.log(`Cloudinary authentication verified for ${cloudName}.`);
}

try {
  await checkCloudinary();
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
