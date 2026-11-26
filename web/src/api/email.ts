import { api } from '@/lib/api'

export interface SignedLink {
  user: string
  hash: string
  expires: string
  signature: string
}

export async function verifyEmail({ user, hash, expires, signature }: SignedLink): Promise<void> {
  await api.post(`/email-verifications/${user}/${hash}`, null, { params: { expires, signature } })
}

export async function confirmEmailChange({ user, hash, expires, signature }: SignedLink): Promise<void> {
  await api.post(`/email-changes/${user}/${hash}`, null, { params: { expires, signature } })
}
