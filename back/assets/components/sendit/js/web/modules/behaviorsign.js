import {Base} from './base.js';

export class BehaviorSign extends Base {
  initialize() {
    this.encryptionKey = null;
    this.keyHex = this.hub.getComponentCookie('sibehaviorkey') || '';

    document.addEventListener('si:send:after', (e) => {
      const newKey = e.detail?.result?.data?._newBehaviorKey;
      if (newKey) {
        this.keyHex = newKey;
        this.encryptionKey = null;
        this.hub.setComponentCookie('sibehaviorkey', newKey);
      }
    });
  }

  async importKey() {
    if (this.encryptionKey) return this.encryptionKey;
    if (!this.keyHex) {
      throw new Error('Behavior encryption key not found');
    }
    const keyBytes = this.hexToBytes(this.keyHex);
    this.encryptionKey = await crypto.subtle.importKey(
      'raw', keyBytes, {name: 'AES-GCM'}, false, ['encrypt']
    );
    return this.encryptionKey;
  }

  async createSignature() {
    const analysisResult = this.hub.SessionLogger?.requestAnalysis();
    if (!analysisResult) {
      throw new Error('SessionLogger not available');
    }

    const powChallenge = this.hub.getComponentCookie('sipowchallenge') || '';
    const payload = {
      score: analysisResult.score,
      isBot: analysisResult.isBot,
      details: analysisResult.details,
      timestamp: analysisResult.timestamp,
      formFillTimes: analysisResult.formFillTimes || {},
      powChallenge: powChallenge
    };

    const key = await this.importKey();
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const plaintext = new TextEncoder().encode(JSON.stringify(payload));

    const encrypted = await crypto.subtle.encrypt(
      {name: 'AES-GCM', iv: iv}, key, plaintext
    );

    const encryptedArray = new Uint8Array(encrypted);
    const result = new Uint8Array(iv.length + encryptedArray.length);
    result.set(iv);
    result.set(encryptedArray, iv.length);

    return this.bytesToHex(result);
  }

  hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
      bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
    }
    return bytes;
  }

  bytesToHex(bytes) {
    return Array.from(bytes).map(b => b.toString(16).padStart(2, '0')).join('');
  }
}
