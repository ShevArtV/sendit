import {Base} from './base.js';

export class ProofOfWork extends Base {
  initialize() {
    this.challenge = this.hub.getComponentCookie('sipowchallenge') || '';

    document.addEventListener('si:send:after', (e) => {
      const newChallenge = e.detail?.result?.data?._newPowChallenge;
      if (newChallenge) {
        this.challenge = newChallenge;
        this.hub.setComponentCookie('sipowchallenge', newChallenge);
      }
    });
  }

  async solve(difficulty) {
    if (!this.challenge) {
      throw new Error('PoW challenge not found');
    }

    const challengeBytes = this.hexToBytes(this.challenge);
    let nonce = 0;

    while (true) {
      const nonceStr = String(nonce);
      const nonceBytes = new TextEncoder().encode(nonceStr);

      const data = new Uint8Array(challengeBytes.length + nonceBytes.length);
      data.set(challengeBytes);
      data.set(nonceBytes, challengeBytes.length);

      const hashBuffer = await crypto.subtle.digest('SHA-256', data);
      const hashArray = new Uint8Array(hashBuffer);

      if (this.hasLeadingZeroBits(hashArray, difficulty)) {
        return nonceStr;
      }

      nonce++;

      if (nonce % 1000 === 0) {
        await new Promise(resolve => setTimeout(resolve, 0));
      }
    }
  }

  hasLeadingZeroBits(hash, difficulty) {
    let zeroBits = 0;
    for (let i = 0; i < hash.length && zeroBits < difficulty; i++) {
      const byte = hash[i];
      if (byte === 0) {
        zeroBits += 8;
      } else {
        zeroBits += Math.clz32(byte) - 24;
        break;
      }
    }
    return zeroBits >= difficulty;
  }

  hexToBytes(hex) {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
      bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
    }
    return bytes;
  }
}
